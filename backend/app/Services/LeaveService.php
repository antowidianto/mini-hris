<?php

namespace App\Services;

use App\Models\ApprovalFlow;
use App\Models\ApprovalStep;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeaveService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly ApprovalFlowService $approvalFlowService
    ) {}

    /**
     * @return Collection<int, LeaveType>
     */
    public function activeTypes(User $user): Collection
    {
        return LeaveType::query()
            ->where('company_id', $user->companyId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * @return Collection<int, LeaveBalance>
     */
    public function balancesFor(User $user, ?int $year = null): Collection
    {
        $employee = $this->employeeForUser($user);
        $year ??= now()->year;

        $this->ensureBalances($employee, $year);

        return LeaveBalance::query()
            ->with('leaveType')
            ->where('company_id', $employee->company_id)
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->orderBy('leave_type_id')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function employeeRequests(User $user, array $filters): LengthAwarePaginator
    {
        $employee = $this->employeeForUser($user);
        $perPage = min((int) ($filters['per_page'] ?? 10), 50);

        return LeaveRequest::query()
            ->with($this->leaveRequestRelations())
            ->where('employee_id', $employee->id)
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['supervisor_status'] ?? null, fn ($query, $status) => $query->where('supervisor_status', $status))
            ->when($filters['hr_status'] ?? null, fn ($query, $status) => $query->where('hr_status', $status))
            ->when($filters['leave_type_id'] ?? null, fn ($query, $leaveTypeId) => $query->where('leave_type_id', $leaveTypeId))
            ->latest('start_date')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function submit(User $user, array $data): LeaveRequest
    {
        $employee = $this->employeeForUser($user);
        $leaveType = LeaveType::query()
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->findOrFail($data['leave_type_id']);
        $startDate = Carbon::parse($data['start_date'])->startOfDay();
        $endDate = Carbon::parse($data['end_date'])->startOfDay();
        $totalDays = $startDate->diffInDays($endDate) + 1;

        if ($startDate->year !== $endDate->year) {
            throw ValidationException::withMessages([
                'end_date' => ['Leave requests must stay within one calendar year.'],
            ]);
        }

        return DB::transaction(function () use ($employee, $leaveType, $startDate, $endDate, $totalDays, $data) {
            $this->ensureNoOverlap($employee, $startDate->toDateString(), $endDate->toDateString());

            $balance = $this->lockedBalance($employee, $leaveType, $startDate->year);
            $availableDays = $this->availableDays($employee, $leaveType, $startDate->year);

            if ($totalDays > $availableDays) {
                throw ValidationException::withMessages([
                    'leave_type_id' => ["Insufficient leave balance. Available days: {$availableDays}."],
                ]);
            }

            $leaveRequest = LeaveRequest::query()->create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'leave_type_id' => $balance->leave_type_id,
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
                'total_days' => $totalDays,
                'reason' => $data['reason'],
                'status' => LeaveRequest::STATUS_PENDING,
                'supervisor_status' => $employee->supervisor_id
                    ? LeaveRequest::DECISION_PENDING
                    : LeaveRequest::DECISION_APPROVED,
                'hr_status' => LeaveRequest::DECISION_PENDING,
            ]);

            $this->approvalFlowService->createRuntimeSteps(
                $leaveRequest,
                ApprovalFlow::MODULE_LEAVE,
                $employee->company_id,
                $employee
            );

            $this->syncLeaveApprovalState($leaveRequest);

            return $leaveRequest->refresh()->load($this->leaveRequestRelations());
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function approvalRequests(User $actor, array $filters): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 10), 50);
        $status = $filters['status'] ?? LeaveRequest::STATUS_PENDING;

        return LeaveRequest::query()
            ->with($this->leaveRequestRelations())
            ->where('company_id', $actor->companyId())
            ->whereHas('approvalSteps', fn ($query) => $query
                ->where('status', ApprovalStep::STATUS_PENDING)
                ->whereIn('role', $this->approvalRolesFor($actor))
                ->whereRaw('step_order = (
                    select min(inner_steps.step_order)
                    from approval_steps as inner_steps
                    where inner_steps.approvable_type = approval_steps.approvable_type
                    and inner_steps.approvable_id = approval_steps.approvable_id
                    and inner_steps.status = ?
                )', [ApprovalStep::STATUS_PENDING]))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($filters['supervisor_status'] ?? null, fn ($query, $supervisorStatus) => $query->where('supervisor_status', $supervisorStatus))
            ->when($filters['hr_status'] ?? null, fn ($query, $hrStatus) => $query->where('hr_status', $hrStatus))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['leave_type_id'] ?? null, fn ($query, $leaveTypeId) => $query->where('leave_type_id', $leaveTypeId))
            ->oldest('start_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function supervisorApprovalRequests(User $user, array $filters): LengthAwarePaginator
    {
        $supervisor = $this->employeeForUser($user);
        $directReportIds = $supervisor->directReports()->pluck('id');
        $perPage = min((int) ($filters['per_page'] ?? 10), 50);
        $status = $filters['status'] ?? LeaveRequest::STATUS_PENDING;
        $supervisorStatus = $filters['supervisor_status'] ?? LeaveRequest::DECISION_PENDING;

        return LeaveRequest::query()
            ->with($this->leaveRequestRelations())
            ->where('company_id', $supervisor->company_id)
            ->whereIn('employee_id', $directReportIds)
            ->whereHas('approvalSteps', fn ($query) => $query
                ->where('status', ApprovalStep::STATUS_PENDING)
                ->where('role', 'supervisor')
                ->whereRaw('step_order = (
                    select min(inner_steps.step_order)
                    from approval_steps as inner_steps
                    where inner_steps.approvable_type = approval_steps.approvable_type
                    and inner_steps.approvable_id = approval_steps.approvable_id
                    and inner_steps.status = ?
                )', [ApprovalStep::STATUS_PENDING]))
            ->when($status, fn ($query) => $query->where('status', $status))
            ->when($supervisorStatus, fn ($query) => $query->where('supervisor_status', $supervisorStatus))
            ->when($filters['hr_status'] ?? null, fn ($query, $hrStatus) => $query->where('hr_status', $hrStatus))
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['leave_type_id'] ?? null, fn ($query, $leaveTypeId) => $query->where('leave_type_id', $leaveTypeId))
            ->oldest('start_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function supervisorApprove(LeaveRequest $leaveRequest, User $supervisorUser, ?string $notes = null): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $notes, $supervisorUser) {
            $leaveRequest = $this->lockedLeaveRequest($leaveRequest);
            $this->ensureSameCompany($leaveRequest, $supervisorUser);
            $this->ensurePending($leaveRequest);
            $this->ensureDirectSupervisor($leaveRequest, $supervisorUser);
            $step = $this->ensureCurrentStep($leaveRequest, 'supervisor');

            $step = $this->approvalFlowService->decideStep($step, $supervisorUser, ApprovalStep::STATUS_APPROVED, $notes);
            $this->completeLeaveApprovalIfReady($leaveRequest, $supervisorUser, $notes);
            $this->syncLeaveApprovalState($leaveRequest, $step, $notes);

            $this->auditLogService->record(
                $supervisorUser,
                AuditLog::ACTION_APPROVED,
                AuditLog::MODULE_LEAVE,
                "Supervisor approved leave request #{$leaveRequest->id} for {$leaveRequest->employee->full_name}."
            );

            return $leaveRequest->refresh()->load($this->leaveRequestRelations());
        });
    }

    public function supervisorReject(LeaveRequest $leaveRequest, User $supervisorUser, ?string $notes = null): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $notes, $supervisorUser) {
            $leaveRequest = $this->lockedLeaveRequest($leaveRequest);
            $this->ensureSameCompany($leaveRequest, $supervisorUser);
            $this->ensurePending($leaveRequest);
            $this->ensureDirectSupervisor($leaveRequest, $supervisorUser);
            $step = $this->ensureCurrentStep($leaveRequest, 'supervisor');

            $step = $this->approvalFlowService->decideStep($step, $supervisorUser, ApprovalStep::STATUS_REJECTED, $notes);
            $this->rejectLeaveApproval($leaveRequest, $supervisorUser, $step, $notes);

            $this->auditLogService->record(
                $supervisorUser,
                AuditLog::ACTION_REJECTED,
                AuditLog::MODULE_LEAVE,
                "Supervisor rejected leave request #{$leaveRequest->id} for {$leaveRequest->employee->full_name}."
            );

            return $leaveRequest->refresh()->load($this->leaveRequestRelations());
        });
    }

    public function approve(LeaveRequest $leaveRequest, User $approver, ?string $notes = null): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $notes) {
            $leaveRequest = $this->lockedLeaveRequest($leaveRequest);
            $this->ensureSameCompany($leaveRequest, $approver);

            $this->ensurePending($leaveRequest);
            $step = $this->ensureCurrentManagementStep($leaveRequest);

            $step = $this->approvalFlowService->decideCurrentStep($leaveRequest, $approver, ApprovalStep::STATUS_APPROVED, $notes);
            $this->completeLeaveApprovalIfReady($leaveRequest, $approver, $notes);
            $this->syncLeaveApprovalState($leaveRequest, $step, $notes);
            $this->auditLogService->record(
                $approver,
                AuditLog::ACTION_APPROVED,
                AuditLog::MODULE_LEAVE,
                "Approved leave request #{$leaveRequest->id} for {$leaveRequest->employee->full_name}."
            );

            return $leaveRequest->refresh()->load($this->leaveRequestRelations());
        });
    }

    public function reject(LeaveRequest $leaveRequest, User $approver, ?string $notes = null): LeaveRequest
    {
        return DB::transaction(function () use ($leaveRequest, $approver, $notes) {
            $leaveRequest = $this->lockedLeaveRequest($leaveRequest);
            $this->ensureSameCompany($leaveRequest, $approver);

            $this->ensurePending($leaveRequest);
            $step = $this->ensureCurrentManagementStep($leaveRequest);

            $step = $this->approvalFlowService->decideCurrentStep($leaveRequest, $approver, ApprovalStep::STATUS_REJECTED, $notes);
            $this->rejectLeaveApproval($leaveRequest, $approver, $step, $notes);
            $this->auditLogService->record(
                $approver,
                AuditLog::ACTION_REJECTED,
                AuditLog::MODULE_LEAVE,
                "Rejected leave request #{$leaveRequest->id} for {$leaveRequest->employee->full_name}."
            );

            return $leaveRequest->refresh()->load($this->leaveRequestRelations());
        });
    }

    private function employeeForUser(User $user): Employee
    {
        $employee = $user->employee()->where('employment_status', Employee::STATUS_ACTIVE)->first();

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee' => ['An active employee profile is required for leave requests.'],
            ]);
        }

        return $employee;
    }

    private function ensureNoOverlap(Employee $employee, string $startDate, string $endDate): void
    {
        $hasOverlap = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('company_id', $employee->company_id)
            ->whereIn('status', [LeaveRequest::STATUS_PENDING, LeaveRequest::STATUS_APPROVED])
            ->overlapping($startDate, $endDate)
            ->exists();

        if ($hasOverlap) {
            throw ValidationException::withMessages([
                'start_date' => ['Leave dates overlap an existing pending or approved request.'],
            ]);
        }
    }

    private function ensurePending(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->status !== LeaveRequest::STATUS_PENDING) {
            throw ValidationException::withMessages([
                'status' => ['Only pending leave requests can be decided.'],
            ]);
        }
    }

    /**
     * @return list<string>
     */
    private function approvalRolesFor(User $actor): array
    {
        if ($actor->role === User::ROLE_ADMIN) {
            return [User::ROLE_ADMIN, User::ROLE_HR];
        }

        return [$actor->role];
    }

    private function ensureRuntimeStepsExist(LeaveRequest $leaveRequest): void
    {
        if ($leaveRequest->approvalSteps()->exists()) {
            return;
        }

        $this->approvalFlowService->createRuntimeSteps(
            $leaveRequest,
            ApprovalFlow::MODULE_LEAVE,
            (int) $leaveRequest->company_id,
            $leaveRequest->employee
        );

        $this->syncLeaveApprovalState($leaveRequest);
    }

    private function ensureCurrentStep(LeaveRequest $leaveRequest, string $role): ApprovalStep
    {
        $this->ensureRuntimeStepsExist($leaveRequest);
        $step = $this->approvalFlowService->currentPendingStep($leaveRequest);

        if (! $step || $step->role !== $role) {
            throw ValidationException::withMessages([
                'supervisor_status' => ['Only leave requests pending supervisor approval can be decided by a supervisor.'],
            ]);
        }

        return $step;
    }

    private function ensureCurrentManagementStep(LeaveRequest $leaveRequest): ApprovalStep
    {
        $this->ensureRuntimeStepsExist($leaveRequest);
        $step = $this->approvalFlowService->currentPendingStep($leaveRequest);

        if (! $step) {
            throw ValidationException::withMessages([
                'hr_status' => ['There is no pending management approval step for this leave request.'],
            ]);
        }

        if ($step->role === 'supervisor') {
            throw ValidationException::withMessages([
                'supervisor_status' => ['Supervisor approval is required before management decision.'],
            ]);
        }

        return $step;
    }

    private function completeLeaveApprovalIfReady(LeaveRequest $leaveRequest, User $approver, ?string $notes): void
    {
        if ($this->approvalFlowService->hasPendingSteps($leaveRequest)) {
            return;
        }

        $balance = $this->lockedBalance($leaveRequest->employee, $leaveRequest->leaveType, $leaveRequest->start_date->year);
        $availableDays = $this->availableDays(
            $leaveRequest->employee,
            $leaveRequest->leaveType,
            $leaveRequest->start_date->year,
            $leaveRequest->id
        );

        if ($leaveRequest->total_days > $availableDays) {
            throw ValidationException::withMessages([
                'leave_type_id' => ["Insufficient leave balance. Available days: {$availableDays}."],
            ]);
        }

        $balance->increment('used_days', $leaveRequest->total_days);

        $leaveRequest->update([
            'status' => LeaveRequest::STATUS_APPROVED,
            'approval_notes' => $notes,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    private function rejectLeaveApproval(LeaveRequest $leaveRequest, User $approver, ApprovalStep $step, ?string $notes): void
    {
        $this->syncLeaveApprovalState($leaveRequest, $step, $notes);

        $leaveRequest->update([
            'status' => LeaveRequest::STATUS_REJECTED,
            'approval_notes' => $notes,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    private function syncLeaveApprovalState(LeaveRequest $leaveRequest, ?ApprovalStep $decidedStep = null, ?string $notes = null): void
    {
        $steps = $leaveRequest->approvalSteps()->with('approver')->orderBy('step_order')->get();

        if ($steps->isEmpty()) {
            return;
        }

        $supervisorStep = $steps->firstWhere('role', 'supervisor');
        $managementSteps = $steps->reject(fn (ApprovalStep $step) => $step->role === 'supervisor');
        $managementStatus = LeaveRequest::DECISION_APPROVED;

        if ($steps->contains('status', ApprovalStep::STATUS_REJECTED)) {
            $managementStatus = LeaveRequest::DECISION_REJECTED;
        } elseif ($managementSteps->contains('status', ApprovalStep::STATUS_PENDING)) {
            $managementStatus = LeaveRequest::DECISION_PENDING;
        }

        $updates = [
            'supervisor_status' => $supervisorStep?->status ?? LeaveRequest::DECISION_APPROVED,
            'hr_status' => $managementStatus,
        ];

        if ($decidedStep?->role === 'supervisor') {
            $updates += [
                'supervisor_notes' => $notes,
                'supervisor_approved_by' => $decidedStep->approved_by,
                'supervisor_approved_at' => $decidedStep->approved_at,
            ];
        } elseif ($decidedStep) {
            $updates += [
                'hr_notes' => $notes,
                'hr_approved_by' => $decidedStep->approved_by,
                'hr_approved_at' => $decidedStep->approved_at,
            ];
        }

        $leaveRequest->update($updates);
    }

    private function ensureDirectSupervisor(LeaveRequest $leaveRequest, User $supervisorUser): void
    {
        $requester = $leaveRequest->employee()->with('supervisor.user')->firstOrFail();

        if ($requester->supervisor?->user_id !== $supervisorUser->id) {
            throw ValidationException::withMessages([
                'supervisor' => ['Only the direct supervisor can decide this request.'],
            ]);
        }
    }

    private function lockedLeaveRequest(LeaveRequest $leaveRequest): LeaveRequest
    {
        return LeaveRequest::query()
            ->whereKey($leaveRequest->id)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function lockedBalance(Employee $employee, LeaveType $leaveType, int $year): LeaveBalance
    {
        LeaveBalance::query()->firstOrCreate(
            [
                'employee_id' => $employee->id,
                'company_id' => $employee->company_id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
            ],
            [
                'entitlement_days' => $leaveType->annual_entitlement,
                'used_days' => 0,
            ]
        );

        return LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('company_id', $employee->company_id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensureBalances(Employee $employee, int $year): void
    {
        LeaveType::query()
            ->where('company_id', $employee->company_id)
            ->where('is_active', true)
            ->get()
            ->each(fn (LeaveType $leaveType) => LeaveBalance::query()->firstOrCreate(
                [
                    'employee_id' => $employee->id,
                    'company_id' => $employee->company_id,
                    'leave_type_id' => $leaveType->id,
                    'year' => $year,
                ],
                [
                    'entitlement_days' => $leaveType->annual_entitlement,
                    'used_days' => 0,
                ]
            ));
    }

    private function availableDays(Employee $employee, LeaveType $leaveType, int $year, ?int $ignoreRequestId = null): int
    {
        $balance = LeaveBalance::query()
            ->where('employee_id', $employee->id)
            ->where('company_id', $employee->company_id)
            ->where('leave_type_id', $leaveType->id)
            ->where('year', $year)
            ->firstOrFail();

        $pendingDays = LeaveRequest::query()
            ->where('employee_id', $employee->id)
            ->where('company_id', $employee->company_id)
            ->where('leave_type_id', $leaveType->id)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->whereYear('start_date', $year)
            ->when($ignoreRequestId, fn ($query) => $query->whereKeyNot($ignoreRequestId))
            ->sum('total_days');

        return max(0, $balance->entitlement_days - $balance->used_days - $pendingDays);
    }

    /**
     * @return list<string>
     */
    private function leaveRequestRelations(): array
    {
        return [
            'employee.department',
            'employee.position',
            'employee.supervisor.user',
            'leaveType',
            'supervisorApprover',
            'hrApprover',
            'approver',
            'approvalSteps.approver',
        ];
    }

    private function ensureSameCompany(LeaveRequest $leaveRequest, User $user): void
    {
        if ((int) $leaveRequest->company_id !== $user->companyId()) {
            abort(404);
        }
    }
}
