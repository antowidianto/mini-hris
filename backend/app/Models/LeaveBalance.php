<?php

namespace App\Models;

use Database\Factories\LeaveBalanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    /** @use HasFactory<LeaveBalanceFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'year',
        'entitlement_days',
        'used_days',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'entitlement_days' => 'integer',
            'used_days' => 'integer',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function remainingDays(): int
    {
        return max(0, $this->entitlement_days - $this->used_days);
    }
}
