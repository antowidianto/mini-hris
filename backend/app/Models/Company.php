<?php

namespace App\Models;

use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    public const SUBSCRIPTION_TRIALING = 'trialing';

    public const SUBSCRIPTION_ACTIVE = 'active';

    public const SUBSCRIPTION_PAST_DUE = 'past_due';

    public const SUBSCRIPTION_CANCELED = 'canceled';

    public const SUBSCRIPTION_STATUSES = [
        self::SUBSCRIPTION_TRIALING,
        self::SUBSCRIPTION_ACTIVE,
        self::SUBSCRIPTION_PAST_DUE,
        self::SUBSCRIPTION_CANCELED,
    ];

    /** @use HasFactory<CompanyFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'slug',
        'address',
        'npwp',
        'logo_path',
        'plan',
        'subscription_status',
        'trial_ends_at',
        'subscription_ends_at',
        'billing_email',
        'employee_limit',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'trial_ends_at' => 'datetime',
            'subscription_ends_at' => 'datetime',
            'employee_limit' => 'integer',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function hasActiveSubscription(): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->subscription_status === self::SUBSCRIPTION_ACTIVE) {
            return $this->subscription_ends_at === null || $this->subscription_ends_at->isFuture();
        }

        if ($this->subscription_status === self::SUBSCRIPTION_TRIALING) {
            return $this->trial_ends_at === null || $this->trial_ends_at->isFuture();
        }

        return false;
    }

    public static function default(): self
    {
        return self::query()->firstOrCreate(
            ['code' => 'DEFAULT'],
            ['name' => 'Mini HRIS Indonesia', 'is_active' => true]
        );
    }
}
