<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    public const TYPE_CONTRACT_EXPIRY = 'contract_expiry';

    public const TYPE_PROBATION_ENDING = 'probation_ending';

    public const TYPE_PENDING_APPROVAL = 'pending_approval';

    public const TYPE_PAYROLL_ALERT = 'payroll_alert';

    public const TYPES = [
        self::TYPE_CONTRACT_EXPIRY,
        self::TYPE_PROBATION_ENDING,
        self::TYPE_PENDING_APPROVAL,
        self::TYPE_PAYROLL_ALERT,
    ];

    public const SEVERITY_INFO = 'info';

    public const SEVERITY_WARNING = 'warning';

    public const SEVERITY_DANGER = 'danger';

    public const SEVERITIES = [
        self::SEVERITY_INFO,
        self::SEVERITY_WARNING,
        self::SEVERITY_DANGER,
    ];

    protected $fillable = [
        'company_id',
        'user_id',
        'type',
        'severity',
        'title',
        'message',
        'action_url',
        'reminder_key',
        'data',
        'triggered_at',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'triggered_at' => 'datetime',
            'read_at' => 'datetime',
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

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query
            ->where('company_id', $user->companyId())
            ->where('user_id', $user->id);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['type'] ?? null, fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['severity'] ?? null, fn (Builder $query, string $severity) => $query->where('severity', $severity))
            ->when(($filters['unread'] ?? false) === true, fn (Builder $query) => $query->whereNull('read_at'));
    }
}
