<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    public const ACTION_APPROVED = 'approved';

    public const ACTION_CREATED = 'created';

    public const ACTION_DEACTIVATED = 'deactivated';

    public const ACTION_DELETED = 'deleted';

    public const ACTION_GENERATED = 'generated';

    public const ACTION_IMPORTED = 'imported';

    public const ACTION_LOGIN = 'login';

    public const ACTION_LOGOUT = 'logout';

    public const ACTION_REJECTED = 'rejected';

    public const ACTION_UPDATED = 'updated';

    public const MODULE_AUTH = 'auth';

    public const MODULE_EMPLOYEE = 'employee';

    public const MODULE_IMPORT_EXPORT = 'import_export';

    public const MODULE_CONTRACT = 'contract';

    public const MODULE_DOCUMENT = 'document';

    public const MODULE_LEAVE = 'leave';

    public const MODULE_PAYROLL = 'payroll';

    public const MODULE_SETTINGS = 'settings';

    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'user_id',
        'action',
        'module',
        'description',
        'ip_address',
        'user_agent',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['module'] ?? null, fn (Builder $query, string $module) => $query->where('module', $module))
            ->when($filters['action'] ?? null, fn (Builder $query, string $action) => $query->where('action', $action))
            ->when($filters['user_id'] ?? null, fn (Builder $query, int $userId) => $query->where('user_id', $userId))
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
    }
}
