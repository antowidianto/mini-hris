<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Document;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class DocumentService
{
    private const STORAGE_DISK = 'local';

    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, User $actor): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 10), 50);

        return Document::query()
            ->with(['employee.department', 'employee.position', 'payroll', 'generator'])
            ->forCompany($actor->companyId())
            ->filter($filters)
            ->latest('issue_date')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function employeeDocuments(User $user, array $filters): LengthAwarePaginator
    {
        $employee = $this->employeeForUser($user);
        $perPage = min((int) ($filters['per_page'] ?? 10), 50);

        return Document::query()
            ->with(['employee.department', 'employee.position', 'payroll', 'generator'])
            ->where('employee_id', $employee->id)
            ->filter($filters)
            ->latest('issue_date')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function generate(array $data, User $actor): Document
    {
        return DB::transaction(function () use ($data, $actor) {
            $companyId = $actor->companyId();
            $employee = $this->companyEmployee((int) $data['employee_id'], $companyId);
            $payroll = $this->payrollForDocument($data, $employee);
            $type = (string) $data['type'];
            $issueDate = $data['issue_date'] ?? now()->toDateString();

            $document = Document::query()->create([
                'company_id' => $companyId,
                'employee_id' => $employee->id,
                'payroll_id' => $payroll?->id,
                'type' => $type,
                'source' => Document::SOURCE_GENERATED,
                'title' => $data['title'] ?? $this->defaultTitle($type, $employee, $payroll),
                'document_number' => $data['document_number'] ?? $this->documentNumber($type),
                'issue_date' => $issueDate,
                'metadata' => $this->metadata($type, $data),
                'generated_by' => $actor->id,
                'generated_at' => now(),
            ])->load(['employee.department', 'employee.position', 'payroll', 'generator']);

            $this->auditLogService->record(
                $actor,
                AuditLog::ACTION_GENERATED,
                AuditLog::MODULE_DOCUMENT,
                "Generated {$document->title} for {$employee->full_name}."
            );

            return $document;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function upload(array $data, UploadedFile $file, User $actor): Document
    {
        $path = null;

        try {
            return DB::transaction(function () use ($data, $file, $actor, &$path) {
                $companyId = $actor->companyId();
                $employee = $this->companyEmployee((int) $data['employee_id'], $companyId);
                $path = $file->store("documents/{$companyId}/{$employee->id}", self::STORAGE_DISK);

                $document = Document::query()->create([
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'type' => $data['type'],
                    'source' => Document::SOURCE_UPLOADED,
                    'title' => $data['title'],
                    'document_number' => $data['document_number'] ?? null,
                    'issue_date' => $data['issue_date'] ?? now()->toDateString(),
                    'file_path' => $path,
                    'original_file_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'file_size' => $file->getSize(),
                    'metadata' => [
                        'notes' => $data['notes'] ?? null,
                    ],
                    'generated_by' => $actor->id,
                ])->load(['employee.department', 'employee.position', 'generator']);

                $this->auditLogService->record(
                    $actor,
                    AuditLog::ACTION_CREATED,
                    AuditLog::MODULE_DOCUMENT,
                    "Uploaded {$document->title} for {$employee->full_name}."
                );

                return $document;
            });
        } catch (Throwable $exception) {
            if ($path) {
                Storage::disk(self::STORAGE_DISK)->delete($path);
            }

            throw $exception;
        }
    }

    public function delete(Document $document, User $actor): void
    {
        $this->authorizeDocument($document, $actor, true);

        DB::transaction(function () use ($document, $actor) {
            if ($document->file_path) {
                Storage::disk(self::STORAGE_DISK)->delete($document->file_path);
            }

            $title = $document->title;
            $employeeName = $document->employee?->full_name ?? 'employee';
            $document->delete();

            $this->auditLogService->record(
                $actor,
                AuditLog::ACTION_DELETED,
                AuditLog::MODULE_DOCUMENT,
                "Deleted {$title} for {$employeeName}."
            );
        });
    }

    public function preview(Document $document, User $actor): Response
    {
        $this->authorizeDocument($document, $actor);

        if ($document->source === Document::SOURCE_UPLOADED) {
            return $this->storedFileResponse($document, false);
        }

        return $this->pdf($document)->stream($this->filename($document));
    }

    public function download(Document $document, User $actor): Response
    {
        $this->authorizeDocument($document, $actor);

        if ($document->source === Document::SOURCE_UPLOADED) {
            return $this->storedFileResponse($document, true);
        }

        return $this->pdf($document)->download($this->filename($document));
    }

    public function authorizeDocument(Document $document, User $actor, bool $adminOnly = false): void
    {
        $document->loadMissing('employee');

        if ((int) $document->company_id !== $actor->companyId()) {
            abort(404);
        }

        if ($actor->hasRole([User::ROLE_ADMIN, User::ROLE_HR])) {
            return;
        }

        if (! $adminOnly && $actor->hasRole(User::ROLE_EMPLOYEE) && $actor->employee?->id === $document->employee_id) {
            return;
        }

        abort(403);
    }

    private function companyEmployee(int $employeeId, int $companyId): Employee
    {
        return Employee::query()
            ->with(['department', 'position', 'company'])
            ->whereKey($employeeId)
            ->where('company_id', $companyId)
            ->firstOrFail();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function payrollForDocument(array $data, Employee $employee): ?Payroll
    {
        if ($data['type'] !== Document::TYPE_PAYSLIP) {
            return null;
        }

        if (empty($data['payroll_id'])) {
            throw ValidationException::withMessages([
                'payroll_id' => ['Payroll is required to generate a payslip PDF.'],
            ]);
        }

        $payroll = Payroll::query()
            ->whereKey($data['payroll_id'])
            ->where('employee_id', $employee->id)
            ->where('company_id', $employee->company_id)
            ->first();

        if (! $payroll) {
            throw ValidationException::withMessages([
                'payroll_id' => ['Selected payroll does not belong to this employee.'],
            ]);
        }

        return $payroll;
    }

    private function employeeForUser(User $user): Employee
    {
        $employee = $user->employee;

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee' => ['An employee profile is required to view documents.'],
            ]);
        }

        return $employee;
    }

    private function pdf(Document $document): mixed
    {
        $document->loadMissing(['employee.department', 'employee.position', 'employee.company', 'payroll', 'generator']);

        return Pdf::loadView($this->viewName($document), [
            'document' => $document,
            'employee' => $document->employee,
            'payroll' => $document->payroll,
            'metadata' => $document->metadata ?? [],
            'company' => $document->employee->company,
        ])->setPaper('a4');
    }

    private function storedFileResponse(Document $document, bool $download): Response
    {
        if (! $document->file_path || ! Storage::disk(self::STORAGE_DISK)->exists($document->file_path)) {
            abort(404);
        }

        $name = $document->original_file_name ?: $this->filename($document);

        return $download
            ? Storage::disk(self::STORAGE_DISK)->download($document->file_path, $name)
            : Storage::disk(self::STORAGE_DISK)->response($document->file_path, $name);
    }

    private function viewName(Document $document): string
    {
        return [
            Document::TYPE_PAYSLIP => 'pdf.documents.payslip',
            Document::TYPE_EMPLOYMENT_CERTIFICATE => 'pdf.documents.employment-certificate',
            Document::TYPE_CONTRACT_LETTER => 'pdf.documents.contract-letter',
            Document::TYPE_WARNING_LETTER => 'pdf.documents.warning-letter',
        ][$document->type];
    }

    private function defaultTitle(string $type, Employee $employee, ?Payroll $payroll): string
    {
        return match ($type) {
            Document::TYPE_PAYSLIP => 'Payslip '.$payroll?->period_year.'-'.str_pad((string) $payroll?->period_month, 2, '0', STR_PAD_LEFT),
            Document::TYPE_EMPLOYMENT_CERTIFICATE => 'Employment Certificate - '.$employee->full_name,
            Document::TYPE_CONTRACT_LETTER => 'Contract Letter - '.$employee->full_name,
            Document::TYPE_WARNING_LETTER => 'Warning Letter - '.$employee->full_name,
            default => 'Employee Document - '.$employee->full_name,
        };
    }

    private function documentNumber(string $type): string
    {
        $prefix = [
            Document::TYPE_PAYSLIP => 'PAY',
            Document::TYPE_EMPLOYMENT_CERTIFICATE => 'SKK',
            Document::TYPE_CONTRACT_LETTER => 'CTR',
            Document::TYPE_WARNING_LETTER => 'SP',
        ][$type] ?? 'DOC';

        return $prefix.'/'.now()->format('Ymd').'/'.Str::upper(Str::random(6));
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function metadata(string $type, array $data): array
    {
        return [
            'effective_date' => $data['effective_date'] ?? null,
            'warning_level' => $type === Document::TYPE_WARNING_LETTER ? ($data['warning_level'] ?? 'SP1') : null,
            'notes' => $data['notes'] ?? null,
            'signer_name' => $data['signer_name'] ?? null,
            'signer_title' => $data['signer_title'] ?? 'HR Manager',
        ];
    }

    private function filename(Document $document): string
    {
        return Str::slug($document->document_number ?: $document->title).'.pdf';
    }
}
