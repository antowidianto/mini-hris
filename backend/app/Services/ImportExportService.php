<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ImportJob;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportService
{
    public function __construct(private readonly AuditLogService $auditLogService, private readonly AttendanceService $attendanceService) {}

    /**
     * @param  array<string, mixed>  $options
     */
    public function importEmployees(UploadedFile $file, User $actor, array $options = []): ImportJob
    {
        $rows = $this->readCsv($file);
        $failures = [];
        $successRows = 0;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $validator = Validator::make($row, [
                'employee_id' => [
                    'required',
                    'string',
                    'max:50',
                    Rule::unique('employees', 'employee_id')->where('company_id', $actor->companyId()),
                ],
                'full_name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:employees,email'],
                'department' => ['required', 'string', 'max:255'],
                'position' => ['required', 'string', 'max:255'],
                'branch_code' => ['nullable', 'string', 'max:20'],
                'branch_name' => ['nullable', 'string', 'max:255'],
                'join_date' => ['required', 'date'],
                'employment_status' => ['nullable', Rule::in(Employee::STATUSES)],
                'employment_type' => ['nullable', Rule::in(Employee::EMPLOYMENT_TYPES)],
                'contract_start_date' => ['nullable', 'date'],
                'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
                'basic_salary' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
                'nik_ktp' => ['nullable', 'digits:16', 'unique:employees,nik_ktp'],
                'npwp' => ['nullable', 'string', 'max:30', 'unique:employees,npwp'],
                'tax_marital_status' => ['nullable', Rule::in(Employee::TAX_STATUSES)],
                'tax_dependents' => ['nullable', 'integer', 'min:0', 'max:3'],
                'bank_name' => ['nullable', 'string', 'max:255'],
                'bank_account_number' => ['nullable', 'string', 'max:50'],
                'bank_account_holder_name' => ['nullable', 'string', 'max:255'],
            ]);

            if ($validator->fails()) {
                $failures[] = $this->failure($rowNumber, $row, $validator->errors()->all());

                continue;
            }

            try {
                DB::transaction(function () use ($actor, $row) {
                    $department = Department::query()->firstOrCreate(
                        ['company_id' => $actor->companyId(), 'name' => trim($row['department'])],
                        ['description' => null]
                    );
                    $position = Position::query()->firstOrCreate(
                        [
                            'company_id' => $actor->companyId(),
                            'department_id' => $department->id,
                            'name' => trim($row['position']),
                        ],
                        ['description' => null]
                    );
                    $branch = $this->branchFromRow($row, $actor);

                    Employee::query()->create([
                        'company_id' => $actor->companyId(),
                        'branch_id' => $branch?->id,
                        'department_id' => $department->id,
                        'position_id' => $position->id,
                        'employee_id' => trim($row['employee_id']),
                        'full_name' => trim($row['full_name']),
                        'email' => trim($row['email']),
                        'nik_ktp' => ($row['nik_ktp'] ?? '') ?: null,
                        'npwp' => ($row['npwp'] ?? '') ?: null,
                        'tax_marital_status' => ($row['tax_marital_status'] ?? '') ?: Employee::TAX_STATUS_SINGLE,
                        'tax_dependents' => (int) (($row['tax_dependents'] ?? '') ?: 0),
                        'bank_name' => ($row['bank_name'] ?? '') ?: null,
                        'bank_account_number' => ($row['bank_account_number'] ?? '') ?: null,
                        'bank_account_holder_name' => ($row['bank_account_holder_name'] ?? '') ?: null,
                        'join_date' => Carbon::parse($row['join_date'])->toDateString(),
                        'employment_status' => ($row['employment_status'] ?? '') ?: Employee::STATUS_ACTIVE,
                        'employment_type' => ($row['employment_type'] ?? '') ?: Employee::EMPLOYMENT_TYPE_PKWTT,
                        'contract_start_date' => ($row['contract_start_date'] ?? '') ?: null,
                        'contract_end_date' => ($row['contract_end_date'] ?? '') ?: null,
                        'basic_salary' => round((float) $row['basic_salary'], 2),
                    ]);
                });

                $successRows++;
            } catch (\Throwable $exception) {
                $failures[] = $this->failure($rowNumber, $row, [$exception->getMessage()]);
            }
        }

        $job = $this->recordJob($actor, ImportJob::TYPE_EMPLOYEES, $file, count($rows), $successRows, $failures, [
            'mode' => $options['mode'] ?? 'create',
            'supported_format' => 'csv',
        ]);

        $this->auditLogService->record(
            $actor,
            AuditLog::ACTION_IMPORTED,
            AuditLog::MODULE_IMPORT_EXPORT,
            "Imported employees from {$file->getClientOriginalName()}: {$successRows} succeeded, ".count($failures).' failed.'
        );

        return $job;
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function importAttendance(UploadedFile $file, User $actor, array $options = []): ImportJob
    {
        $rows = $this->readCsv($file);
        $failures = [];
        $successRows = 0;
        $importBatch = 'ATT-'.now()->format('YmdHis').'-'.$actor->id;
        $defaultSource = in_array(($options['source'] ?? null), [Attendance::SOURCE_FINGERPRINT, Attendance::SOURCE_IMPORT], true)
            ? $options['source']
            : Attendance::SOURCE_FINGERPRINT;

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $validator = Validator::make($row, [
                'employee_id' => ['required', 'string', 'max:50'],
                'attendance_date' => ['required', 'date'],
                'time_in' => ['nullable', 'date_format:H:i'],
                'time_out' => ['nullable', 'date_format:H:i'],
                'status' => ['required', Rule::in(Attendance::STATUSES)],
                'source' => ['nullable', Rule::in([Attendance::SOURCE_FINGERPRINT, Attendance::SOURCE_IMPORT])],
                'notes' => ['nullable', 'string', 'max:1000'],
            ])->after(function ($validator) use ($row) {
                if (! ($row['time_in'] ?? null) || ! ($row['time_out'] ?? null)) {
                    return;
                }

                if (! preg_match('/^\d{2}:\d{2}$/', $row['time_in']) || ! preg_match('/^\d{2}:\d{2}$/', $row['time_out'])) {
                    return;
                }

                if ($row['time_out'] < $row['time_in']) {
                    $validator->errors()->add('time_out', 'Clock out cannot happen before clock in.');
                }
            });

            if ($validator->fails()) {
                $failures[] = $this->failure($rowNumber, $row, $validator->errors()->all());

                continue;
            }

            $employee = Employee::query()
                ->where('company_id', $actor->companyId())
                ->where('employee_id', trim($row['employee_id']))
                ->first();

            if (! $employee) {
                $failures[] = $this->failure($rowNumber, $row, ['Employee ID was not found in your company.']);

                continue;
            }

            try {
                $attendanceDate = Carbon::parse($row['attendance_date'])->toDateString();
                $timeOut = $row['time_out'] ? $this->normalizeTime($row['time_out']) : null;
                $timeIn = $row['time_in'] ? $this->normalizeTime($row['time_in']) : null;
                $overtimeMinutes = $timeIn && $timeOut
                    ? $this->attendanceService->overtimeMinutesForValues($attendanceDate, $timeOut, $employee->company_id)
                    : 0;

                Attendance::query()->updateOrCreate(
                    [
                        'company_id' => $actor->companyId(),
                        'employee_id' => $employee->id,
                        'attendance_date' => $attendanceDate,
                    ],
                    [
                        'time_in' => $timeIn,
                        'time_out' => $timeOut,
                        'status' => $row['status'],
                        'attendance_source' => ($row['source'] ?? '') ?: $defaultSource,
                        'import_batch' => $importBatch,
                        'overtime_minutes' => $overtimeMinutes,
                        'notes' => ($row['notes'] ?? '') ?: null,
                    ]
                );

                $successRows++;
            } catch (\Throwable $exception) {
                $failures[] = $this->failure($rowNumber, $row, [$exception->getMessage()]);
            }
        }

        $job = $this->recordJob($actor, ImportJob::TYPE_ATTENDANCE, $file, count($rows), $successRows, $failures, [
            'source' => $defaultSource,
            'import_batch' => $importBatch,
            'supported_format' => 'csv',
        ]);

        $this->auditLogService->record(
            $actor,
            AuditLog::ACTION_IMPORTED,
            AuditLog::MODULE_IMPORT_EXPORT,
            "Imported attendance from {$file->getClientOriginalName()}: {$successRows} succeeded, ".count($failures).' failed.'
        );

        return $job;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportEmployees(User $actor, array $filters): StreamedResponse
    {
        $rows = Employee::query()
            ->forCompany($actor->companyId())
            ->with(['branch', 'department', 'position'])
            ->search($filters['search'] ?? null)
            ->when($filters['employment_status'] ?? null, fn ($query, $status) => $query->where('employment_status', $status))
            ->when($filters['employment_type'] ?? null, fn ($query, $type) => $query->where('employment_type', $type))
            ->orderBy('employee_id')
            ->get()
            ->map(fn (Employee $employee) => [
                $employee->employee_id,
                $employee->full_name,
                $employee->email,
                $employee->branch?->code,
                $employee->branch?->name,
                $employee->department?->name,
                $employee->position?->name,
                $employee->join_date?->format('Y-m-d'),
                $employee->employment_status,
                $employee->employment_type,
                $employee->basic_salary,
                $employee->nik_ktp,
                $employee->npwp,
                $employee->tax_marital_status,
                $employee->tax_dependents,
                $employee->bank_name,
                $employee->bank_account_number,
                $employee->bank_account_holder_name,
            ]);

        return $this->csvDownload('employees-export-'.now()->format('Ymd-His').'.csv', [
            'employee_id',
            'full_name',
            'email',
            'branch_code',
            'branch_name',
            'department',
            'position',
            'join_date',
            'employment_status',
            'employment_type',
            'basic_salary',
            'nik_ktp',
            'npwp',
            'tax_marital_status',
            'tax_dependents',
            'bank_name',
            'bank_account_number',
            'bank_account_holder_name',
        ], $rows->all());
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportPayroll(User $actor, array $filters): StreamedResponse
    {
        $rows = Payroll::query()
            ->with(['employee.department', 'employee.position'])
            ->where('company_id', $actor->companyId())
            ->forPeriod($filters['period_year'] ?? null, $filters['period_month'] ?? null)
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->latest('period_year')
            ->latest('period_month')
            ->get()
            ->map(fn (Payroll $payroll) => [
                sprintf('%04d-%02d', $payroll->period_year, $payroll->period_month),
                $payroll->employee?->employee_id,
                $payroll->employee?->full_name,
                $payroll->employee?->department?->name,
                $payroll->basic_salary,
                $payroll->allowance,
                $payroll->gross_salary,
                $payroll->attendance_deduction,
                $payroll->late_deduction,
                $payroll->unpaid_leave_deduction,
                $payroll->total_employee_bpjs,
                $payroll->pph21_deduction,
                $payroll->other_deduction,
                $payroll->take_home_pay,
                $payroll->generated_at?->toDateTimeString(),
            ]);

        return $this->csvDownload('payroll-export-'.now()->format('Ymd-His').'.csv', [
            'period',
            'employee_id',
            'full_name',
            'department',
            'basic_salary',
            'allowance',
            'gross_salary',
            'attendance_deduction',
            'late_deduction',
            'unpaid_leave_deduction',
            'total_employee_bpjs',
            'pph21_deduction',
            'other_deduction',
            'take_home_pay',
            'generated_at',
        ], $rows->all());
    }

    /**
     * @param  array<int, array<string, mixed>>  $recap
     */
    public function exportAttendanceRecap(array $recap, int $year, int $month): StreamedResponse
    {
        $rows = collect($recap)->map(fn (array $row) => [
            sprintf('%04d-%02d', $year, $month),
            $row['employee']['employee_id'] ?? null,
            $row['employee']['full_name'] ?? null,
            $row['employee']['department'] ?? null,
            $row['employee']['position'] ?? null,
            $row['present_days'],
            $row['late_days'],
            $row['sick_days'],
            $row['permission_days'],
            $row['leave_days'],
            $row['alpha_days'],
            $row['overtime_minutes'],
            $row['total_records'],
        ]);

        return $this->csvDownload('attendance-recap-'.sprintf('%04d-%02d', $year, $month).'.csv', [
            'period',
            'employee_id',
            'full_name',
            'department',
            'position',
            'present_days',
            'late_days',
            'sick_days',
            'permission_days',
            'leave_days',
            'alpha_days',
            'overtime_minutes',
            'total_records',
        ], $rows->all());
    }

    private function branchFromRow(array $row, User $actor): ?Branch
    {
        if (! ($row['branch_code'] ?? null) && ! ($row['branch_name'] ?? null)) {
            return null;
        }

        $code = trim(($row['branch_code'] ?? '') ?: $row['branch_name']);
        $name = trim(($row['branch_name'] ?? '') ?: $code);

        return Branch::query()->firstOrCreate(
            ['company_id' => $actor->companyId(), 'code' => $code],
            ['name' => $name, 'type' => Branch::TYPE_BRANCH, 'is_active' => true]
        );
    }

    /**
     * @return list<array<string, string>>
     */
    private function readCsv(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'rb');

        if (! $handle) {
            return [];
        }

        $header = null;
        $rows = [];

        while (($line = fgetcsv($handle)) !== false) {
            if ($header === null) {
                $header = array_map(fn ($column) => $this->normalizeHeader((string) $column), $line);

                continue;
            }

            if ($this->blankCsvLine($line)) {
                continue;
            }

            $row = [];

            foreach ($header as $index => $column) {
                $row[$column] = trim((string) ($line[$index] ?? ''));
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    private function normalizeHeader(string $header): string
    {
        $header = preg_replace('/^\xEF\xBB\xBF/', '', $header) ?? $header;

        return strtolower(trim(str_replace([' ', '-'], '_', $header)));
    }

    /**
     * @param  list<string|null>  $line
     */
    private function blankCsvLine(array $line): bool
    {
        return collect($line)->every(fn ($value) => trim((string) $value) === '');
    }

    /**
     * @param  array<string, string>  $row
     * @param  list<string>  $errors
     * @return array<string, mixed>
     */
    private function failure(int $rowNumber, array $row, array $errors): array
    {
        return [
            'row' => $rowNumber,
            'errors' => $errors,
            'data' => $row,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $failures
     * @param  array<string, mixed>  $summary
     */
    private function recordJob(User $actor, string $type, UploadedFile $file, int $totalRows, int $successRows, array $failures, array $summary): ImportJob
    {
        $failedRows = count($failures);

        return ImportJob::query()->create([
            'company_id' => $actor->companyId(),
            'user_id' => $actor->id,
            'type' => $type,
            'file_name' => $file->getClientOriginalName(),
            'status' => $failedRows > 0 ? ImportJob::STATUS_COMPLETED_WITH_ERRORS : ImportJob::STATUS_COMPLETED,
            'total_rows' => $totalRows,
            'success_rows' => $successRows,
            'failed_rows' => $failedRows,
            'summary' => $summary,
            'failures' => array_slice($failures, 0, 50),
        ]);
    }

    /**
     * @param  list<string>  $headers
     * @param  list<array<int, mixed>>  $rows
     */
    private function csvDownload(string $fileName, array $headers, array $rows): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows) {
            $output = fopen('php://output', 'wb');
            fputcsv($output, $headers);

            foreach ($rows as $row) {
                fputcsv($output, array_map(fn ($value) => $this->csvValue($value), $row));
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function normalizeTime(string $value): string
    {
        return Carbon::createFromFormat('H:i', $value)->format('H:i:s');
    }

    private function csvValue(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        return in_array($value[0], ['=', '+', '-', '@'], true) ? "'{$value}" : $value;
    }
}
