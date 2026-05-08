<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PayrollService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $data
     * @return Collection<int, Payroll>
     */
    public function generate(array $data, User $generator): Collection
    {
        $year = (int) $data['period_year'];
        $month = (int) $data['period_month'];
        $companyId = $generator->companyId();
        $settings = CompanySetting::query()->firstOrCreate(['company_id' => $companyId], CompanySetting::defaults());

        return DB::transaction(function () use ($companyId, $data, $year, $month, $settings, $generator) {
            $employees = Employee::query()
                ->with(['department', 'position', 'user'])
                ->where('company_id', $companyId)
                ->where('employment_status', Employee::STATUS_ACTIVE)
                ->when($data['employee_id'] ?? null, fn ($query, $employeeId) => $query->whereKey($employeeId))
                ->orderBy('full_name')
                ->lockForUpdate()
                ->get();

            if ($employees->isEmpty()) {
                throw ValidationException::withMessages([
                    'employee_id' => ['No active employees found for payroll generation.'],
                ]);
            }

            $duplicate = Payroll::query()
                ->whereIn('employee_id', $employees->pluck('id'))
                ->where('period_year', $year)
                ->where('period_month', $month)
                ->with('employee')
                ->first();

            if ($duplicate) {
                throw ValidationException::withMessages([
                    'period_month' => ["Payroll already exists for {$duplicate->employee->full_name} in {$year}-".str_pad((string) $month, 2, '0', STR_PAD_LEFT).'.'],
                ]);
            }

            $payrolls = $employees
                ->map(fn (Employee $employee) => $this->createPayroll($employee, $year, $month, $data, $settings, $generator))
                ->values();
            $this->auditLogService->record(
                $generator,
                AuditLog::ACTION_GENERATED,
                AuditLog::MODULE_PAYROLL,
                'Generated payroll for '.$payrolls->count().' employee(s) for '.sprintf('%04d-%02d', $year, $month).'.'
            );

            return $payrolls;
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, User $actor): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 10), 50);

        return Payroll::query()
            ->with(['employee.department', 'employee.position', 'generator'])
            ->where('company_id', $actor->companyId())
            ->forPeriod($filters['period_year'] ?? null, $filters['period_month'] ?? null)
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->latest('period_year')
            ->latest('period_month')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function payslips(User $user, array $filters): LengthAwarePaginator
    {
        $employee = $this->employeeForUser($user);
        $perPage = min((int) ($filters['per_page'] ?? 10), 50);

        return Payroll::query()
            ->with(['employee.department', 'employee.position', 'generator'])
            ->where('employee_id', $employee->id)
            ->forPeriod($filters['period_year'] ?? null, $filters['period_month'] ?? null)
            ->latest('period_year')
            ->latest('period_month')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function payslip(User $user, Payroll $payroll): Payroll
    {
        $employee = $this->employeeForUser($user);

        if ($payroll->employee_id !== $employee->id) {
            throw ValidationException::withMessages([
                'payroll' => ['You can only view your own payslip.'],
            ]);
        }

        return $payroll->load(['employee.department', 'employee.position', 'generator']);
    }

    private function createPayroll(
        Employee $employee,
        int $year,
        int $month,
        array $data,
        CompanySetting $settings,
        User $generator
    ): Payroll {
        $periodStart = Carbon::create($year, $month, 1)->startOfDay();
        $periodEnd = $periodStart->copy()->endOfMonth();
        $basicSalary = (float) $employee->basic_salary;
        $workDays = max(1, (int) $settings->payroll_work_days_per_month);
        $dailyRate = round($basicSalary / $workDays, 2);
        $fixedAllowance = $this->componentAmount($data, 'fixed_allowance', $settings->payroll_fixed_allowance_default);
        $nonFixedAllowance = $this->componentAmount($data, 'non_fixed_allowance', $settings->payroll_non_fixed_allowance_default);
        $mealAllowance = $this->componentAmount($data, 'meal_allowance', $settings->meal_allowance_default);
        $transportAllowance = $this->componentAmount($data, 'transport_allowance', $settings->transport_allowance_default);
        $allowance = round($fixedAllowance + $nonFixedAllowance + $mealAllowance + $transportAllowance, 2);
        $grossSalary = round($basicSalary + $allowance, 2);
        $otherDeduction = $this->componentAmount($data, 'other_deduction', 0);
        $pph21Deduction = $this->componentAmount($data, 'pph21_deduction', $settings->pph21_default_deduction);
        $absentDays = $this->attendanceDays($employee, $periodStart, $periodEnd, [Attendance::STATUS_ABSENT, Attendance::STATUS_ALPHA]);
        $lateDays = $this->attendanceDays($employee, $periodStart, $periodEnd, Attendance::STATUS_LATE);
        $unpaidLeaveDays = $this->unpaidLeaveDays($employee, $periodStart, $periodEnd);
        $attendanceDeduction = round($absentDays * $dailyRate, 2);
        $lateDeduction = round($lateDays * (float) $settings->late_deduction_amount, 2);
        $unpaidLeaveDeduction = round($unpaidLeaveDays * $dailyRate, 2);
        $bpjsKesehatanEmployee = $this->rateAmount($basicSalary, $settings->bpjs_kesehatan_employee_rate);
        $bpjsKesehatanEmployer = $this->rateAmount($basicSalary, $settings->bpjs_kesehatan_employer_rate);
        $bpjsJhtEmployee = $this->rateAmount($basicSalary, $settings->bpjs_jht_employee_rate);
        $bpjsJhtEmployer = $this->rateAmount($basicSalary, $settings->bpjs_jht_employer_rate);
        $bpjsJpEmployee = $this->rateAmount($basicSalary, $settings->bpjs_jp_employee_rate);
        $bpjsJpEmployer = $this->rateAmount($basicSalary, $settings->bpjs_jp_employer_rate);
        $totalEmployeeBpjs = round($bpjsKesehatanEmployee + $bpjsJhtEmployee + $bpjsJpEmployee, 2);
        $totalEmployerBpjs = round($bpjsKesehatanEmployer + $bpjsJhtEmployer + $bpjsJpEmployer, 2);
        $totalDeductions = round(
            $otherDeduction
                + $attendanceDeduction
                + $lateDeduction
                + $unpaidLeaveDeduction
                + $totalEmployeeBpjs
                + $pph21Deduction,
            2
        );
        $takeHomePay = round($grossSalary - $totalDeductions, 2);

        return Payroll::query()->create([
            'employee_id' => $employee->id,
            'company_id' => $employee->company_id,
            'period_year' => $year,
            'period_month' => $month,
            'basic_salary' => $basicSalary,
            'fixed_allowance' => $fixedAllowance,
            'non_fixed_allowance' => $nonFixedAllowance,
            'meal_allowance' => $mealAllowance,
            'transport_allowance' => $transportAllowance,
            'allowance' => $allowance,
            'gross_salary' => $grossSalary,
            'deduction' => $otherDeduction,
            'attendance_deduction' => $attendanceDeduction,
            'late_deduction' => $lateDeduction,
            'unpaid_leave_deduction' => $unpaidLeaveDeduction,
            'bpjs_kesehatan_employee' => $bpjsKesehatanEmployee,
            'bpjs_kesehatan_employer' => $bpjsKesehatanEmployer,
            'bpjs_jht_employee' => $bpjsJhtEmployee,
            'bpjs_jht_employer' => $bpjsJhtEmployer,
            'bpjs_jp_employee' => $bpjsJpEmployee,
            'bpjs_jp_employer' => $bpjsJpEmployer,
            'pph21_deduction' => $pph21Deduction,
            'other_deduction' => $otherDeduction,
            'total_employee_bpjs' => $totalEmployeeBpjs,
            'total_employer_bpjs' => $totalEmployerBpjs,
            'net_salary' => $takeHomePay,
            'take_home_pay' => $takeHomePay,
            'absent_days' => $absentDays,
            'late_days' => $lateDays,
            'unpaid_leave_days' => $unpaidLeaveDays,
            'settings_snapshot' => $this->settingsSnapshot($settings),
            'generated_by' => $generator->id,
            'generated_at' => now(),
        ])->load(['employee.department', 'employee.position', 'generator']);
    }

    /**
     * @param  string|array<int, string>  $status
     */
    private function attendanceDays(Employee $employee, Carbon $periodStart, Carbon $periodEnd, string|array $status): int
    {
        return Attendance::query()
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->whereIn('status', (array) $status)
            ->whereBetween('attendance_date', [$periodStart->toDateString(), $periodEnd->toDateString()])
            ->count();
    }

    private function unpaidLeaveDays(Employee $employee, Carbon $periodStart, Carbon $periodEnd): int
    {
        return LeaveRequest::query()
            ->with('leaveType')
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('status', LeaveRequest::STATUS_APPROVED)
            ->whereHas('leaveType', fn ($query) => $query->where('is_paid', false))
            ->where('start_date', '<=', $periodEnd->toDateString())
            ->where('end_date', '>=', $periodStart->toDateString())
            ->get()
            ->sum(fn (LeaveRequest $leaveRequest) => $this->overlapDays($leaveRequest, $periodStart, $periodEnd));
    }

    private function overlapDays(LeaveRequest $leaveRequest, Carbon $periodStart, Carbon $periodEnd): int
    {
        $startDate = $leaveRequest->start_date->greaterThan($periodStart) ? $leaveRequest->start_date : $periodStart;
        $endDate = $leaveRequest->end_date->lessThan($periodEnd) ? $leaveRequest->end_date : $periodEnd;

        return $startDate->diffInDays($endDate) + 1;
    }

    private function employeeForUser(User $user): Employee
    {
        $employee = $user->employee;

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee' => ['An employee profile is required to view payslips.'],
            ]);
        }

        return $employee;
    }

    private function componentAmount(array $data, string $key, mixed $default): float
    {
        return round((float) ($data[$key] ?? $default ?? 0), 2);
    }

    private function rateAmount(float $amount, mixed $rate): float
    {
        return round($amount * ((float) $rate / 100), 2);
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsSnapshot(CompanySetting $settings): array
    {
        return [
            'payroll_work_days_per_month' => $settings->payroll_work_days_per_month,
            'late_deduction_amount' => $settings->late_deduction_amount,
            'bpjs_kesehatan_employee_rate' => $settings->bpjs_kesehatan_employee_rate,
            'bpjs_kesehatan_employer_rate' => $settings->bpjs_kesehatan_employer_rate,
            'bpjs_jht_employee_rate' => $settings->bpjs_jht_employee_rate,
            'bpjs_jht_employer_rate' => $settings->bpjs_jht_employer_rate,
            'bpjs_jp_employee_rate' => $settings->bpjs_jp_employee_rate,
            'bpjs_jp_employer_rate' => $settings->bpjs_jp_employer_rate,
        ];
    }
}
