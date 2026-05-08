<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Document extends Model
{
    public const TYPE_EMPLOYEE_FILE = 'employee_file';

    public const TYPE_PAYSLIP = 'payslip';

    public const TYPE_EMPLOYMENT_CERTIFICATE = 'employment_certificate';

    public const TYPE_CONTRACT_LETTER = 'contract_letter';

    public const TYPE_WARNING_LETTER = 'warning_letter';

    public const TYPES = [
        self::TYPE_EMPLOYEE_FILE,
        self::TYPE_PAYSLIP,
        self::TYPE_EMPLOYMENT_CERTIFICATE,
        self::TYPE_CONTRACT_LETTER,
        self::TYPE_WARNING_LETTER,
    ];

    public const GENERATED_TYPES = [
        self::TYPE_PAYSLIP,
        self::TYPE_EMPLOYMENT_CERTIFICATE,
        self::TYPE_CONTRACT_LETTER,
        self::TYPE_WARNING_LETTER,
    ];

    public const SOURCE_GENERATED = 'generated';

    public const SOURCE_UPLOADED = 'uploaded';

    public const SOURCES = [
        self::SOURCE_GENERATED,
        self::SOURCE_UPLOADED,
    ];

    protected $fillable = [
        'company_id',
        'employee_id',
        'payroll_id',
        'type',
        'source',
        'title',
        'document_number',
        'issue_date',
        'file_path',
        'original_file_name',
        'mime_type',
        'file_size',
        'metadata',
        'generated_by',
        'generated_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date:Y-m-d',
            'file_size' => 'integer',
            'metadata' => 'array',
            'generated_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['source'] ?? null, fn (Builder $query, string $source) => $query->where('source', $source))
            ->when($filters['employee_id'] ?? null, fn (Builder $query, int $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['issue_date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('issue_date', '>=', $date))
            ->when($filters['issue_date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('issue_date', '<=', $date))
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhereHas('employee', function (Builder $query) use ($search) {
                            $query
                                ->where('employee_id', 'like', "%{$search}%")
                                ->orWhere('full_name', 'like', "%{$search}%");
                        });
                });
            });
    }
}
