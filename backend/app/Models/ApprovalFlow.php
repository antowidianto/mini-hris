<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApprovalFlow extends Model
{
    public const MODULE_LEAVE = 'leave';

    public const MODULE_PAYROLL = 'payroll';

    public const MODULE_REQUEST = 'request';

    public const MODULES = [
        self::MODULE_LEAVE,
        self::MODULE_PAYROLL,
        self::MODULE_REQUEST,
    ];

    protected $fillable = [
        'company_id',
        'module',
        'step_order',
        'role',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function scopeForModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
