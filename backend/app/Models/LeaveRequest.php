<?php

namespace App\Models;

use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class LeaveRequest extends Model
{
    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
    ];

    public const DECISION_PENDING = 'pending';

    public const DECISION_APPROVED = 'approved';

    public const DECISION_REJECTED = 'rejected';

    public const DECISIONS = [
        self::DECISION_PENDING,
        self::DECISION_APPROVED,
        self::DECISION_REJECTED,
    ];

    /** @use HasFactory<LeaveRequestFactory> */
    use HasFactory;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'reason',
        'status',
        'supervisor_status',
        'supervisor_notes',
        'supervisor_approved_by',
        'supervisor_approved_at',
        'hr_status',
        'hr_notes',
        'hr_approved_by',
        'hr_approved_at',
        'approval_notes',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date:Y-m-d',
            'end_date' => 'date:Y-m-d',
            'total_days' => 'integer',
            'supervisor_approved_at' => 'datetime',
            'hr_approved_at' => 'datetime',
            'approved_at' => 'datetime',
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

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function supervisorApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supervisor_approved_by');
    }

    public function hrApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'hr_approved_by');
    }

    public function approvalSteps(): MorphMany
    {
        return $this->morphMany(ApprovalStep::class, 'approvable')->orderBy('step_order');
    }

    public function scopeOverlapping(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query
            ->where('start_date', '<=', $endDate)
            ->where('end_date', '>=', $startDate);
    }
}
