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

    public const ACTIONS = [
        self::ACTION_APPROVED,
        self::ACTION_CREATED,
        self::ACTION_DEACTIVATED,
        self::ACTION_DELETED,
        self::ACTION_GENERATED,
        self::ACTION_IMPORTED,
        self::ACTION_LOGIN,
        self::ACTION_LOGOUT,
        self::ACTION_REJECTED,
        self::ACTION_UPDATED,
    ];

    public const MODULES = [
        self::MODULE_AUTH,
        self::MODULE_CONTRACT,
        self::MODULE_DOCUMENT,
        self::MODULE_EMPLOYEE,
        self::MODULE_IMPORT_EXPORT,
        self::MODULE_LEAVE,
        self::MODULE_PAYROLL,
        self::MODULE_SETTINGS,
    ];

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
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->where(function (Builder $query) use ($search) {
                    $query
                        ->where('description', 'like', "%{$search}%")
                        ->orWhereHas('user', function (Builder $query) use ($search) {
                            $query
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($filters['date_from'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'] ?? null, fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date));
    }

    public function actionLabel(): string
    {
        return self::actionLabels()[$this->action] ?? str($this->action)->replace('_', ' ')->headline()->toString();
    }

    public function moduleLabel(): string
    {
        return self::moduleLabels()[$this->module] ?? str($this->module)->replace('_', ' ')->headline()->toString();
    }

    /**
     * @return array<string, string>
     */
    public static function actionLabels(): array
    {
        return [
            self::ACTION_APPROVED => 'Approved',
            self::ACTION_CREATED => 'Created',
            self::ACTION_DEACTIVATED => 'Deactivated',
            self::ACTION_DELETED => 'Deleted',
            self::ACTION_GENERATED => 'Generated',
            self::ACTION_IMPORTED => 'Imported',
            self::ACTION_LOGIN => 'Signed in',
            self::ACTION_LOGOUT => 'Signed out',
            self::ACTION_REJECTED => 'Rejected',
            self::ACTION_UPDATED => 'Updated',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function moduleLabels(): array
    {
        return [
            self::MODULE_AUTH => 'Authentication',
            self::MODULE_CONTRACT => 'Contracts',
            self::MODULE_DOCUMENT => 'Documents',
            self::MODULE_EMPLOYEE => 'Employees',
            self::MODULE_IMPORT_EXPORT => 'Import & Export',
            self::MODULE_LEAVE => 'Leave',
            self::MODULE_PAYROLL => 'Payroll',
            self::MODULE_SETTINGS => 'Settings',
        ];
    }
}
