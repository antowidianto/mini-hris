<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportJob extends Model
{
    public const TYPE_EMPLOYEES = 'employees';

    public const TYPE_ATTENDANCE = 'attendance';

    public const TYPES = [
        self::TYPE_EMPLOYEES,
        self::TYPE_ATTENDANCE,
    ];

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_COMPLETED_WITH_ERRORS = 'completed_with_errors';

    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'file_name',
        'status',
        'total_rows',
        'success_rows',
        'failed_rows',
        'summary',
        'failures',
    ];

    protected function casts(): array
    {
        return [
            'total_rows' => 'integer',
            'success_rows' => 'integer',
            'failed_rows' => 'integer',
            'summary' => 'array',
            'failures' => 'array',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForCompany(Builder $query, int $companyId): Builder
    {
        return $query->where('company_id', $companyId);
    }
}
