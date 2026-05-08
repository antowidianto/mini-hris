<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Notification;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(User $user, array $filters): LengthAwarePaginator
    {
        $this->syncReminders($user);
        $perPage = min((int) ($filters['per_page'] ?? 10), 50);

        return Notification::query()
            ->forUser($user)
            ->filter($filters)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function unreadCount(User $user): int
    {
        $this->syncReminders($user);

        return Notification::query()
            ->forUser($user)
            ->whereNull('read_at')
            ->count();
    }

    public function markAsRead(Notification $notification, User $user): Notification
    {
        $this->ensureOwner($notification, $user);

        if (! $notification->read_at) {
            $notification->forceFill(['read_at' => now()])->save();
        }

        return $notification->refresh();
    }

    public function markAllAsRead(User $user): int
    {
        return Notification::query()
            ->forUser($user)
            ->whereNull('read_at')
            ->update(['read_at' => now(), 'updated_at' => now()]);
    }

    public function syncReminders(User $user): void
    {
        if ($user->hasRole([User::ROLE_ADMIN, User::ROLE_HR])) {
            $this->syncAdminHrReminders($user);
        }

        if ($user->hasRole(User::ROLE_EMPLOYEE)) {
            $this->syncSupervisorApprovalReminders($user);
        }
    }

    private function syncAdminHrReminders(User $user): void
    {
        $companyId = $user->companyId();
        $this->syncContractReminders($user, $companyId);
        $this->syncProbationReminders($user, $companyId);
        $this->syncHrApprovalReminders($user, $companyId);
        $this->syncPayrollReminder($user, $companyId);
    }

    private function syncContractReminders(User $user, int $companyId): void
    {
        $activeKeys = [];

        Employee::query()
            ->where('company_id', $companyId)
            ->where('employment_status', Employee::STATUS_ACTIVE)
            ->where('employment_type', Employee::EMPLOYMENT_TYPE_PKWT)
            ->whereNotNull('contract_end_date')
            ->whereDate('contract_end_date', '>=', now()->toDateString())
            ->whereDate('contract_end_date', '<=', now()->addDays(60)->toDateString())
            ->get()
            ->each(function (Employee $employee) use (&$activeKeys, $companyId, $user) {
                $days = now()->startOfDay()->diffInDays($employee->contract_end_date->startOfDay());
                $reminderKey = "contract-expiry:{$employee->id}:{$employee->contract_end_date->format('Y-m-d')}";
                $activeKeys[] = $reminderKey;

                $this->upsertReminder($user, [
                    'company_id' => $companyId,
                    'type' => Notification::TYPE_CONTRACT_EXPIRY,
                    'severity' => $days <= 30 ? Notification::SEVERITY_DANGER : Notification::SEVERITY_WARNING,
                    'title' => 'Contract expiring soon',
                    'message' => "{$employee->full_name}'s PKWT contract ends in {$days} day(s).",
                    'action_url' => '/contracts',
                    'reminder_key' => $reminderKey,
                    'data' => [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name,
                        'contract_end_date' => $employee->contract_end_date->format('Y-m-d'),
                        'days_remaining' => $days,
                    ],
                ]);
            });

        $this->markStaleRead($user, Notification::TYPE_CONTRACT_EXPIRY, $activeKeys);
    }

    private function syncProbationReminders(User $user, int $companyId): void
    {
        $activeKeys = [];

        Employee::query()
            ->where('company_id', $companyId)
            ->where('employment_status', Employee::STATUS_ACTIVE)
            ->where('employment_type', Employee::EMPLOYMENT_TYPE_PROBATION)
            ->whereNotNull('contract_end_date')
            ->whereDate('contract_end_date', '>=', now()->toDateString())
            ->whereDate('contract_end_date', '<=', now()->addDays(30)->toDateString())
            ->get()
            ->each(function (Employee $employee) use (&$activeKeys, $companyId, $user) {
                $days = now()->startOfDay()->diffInDays($employee->contract_end_date->startOfDay());
                $reminderKey = "probation-ending:{$employee->id}:{$employee->contract_end_date->format('Y-m-d')}";
                $activeKeys[] = $reminderKey;

                $this->upsertReminder($user, [
                    'company_id' => $companyId,
                    'type' => Notification::TYPE_PROBATION_ENDING,
                    'severity' => $days <= 14 ? Notification::SEVERITY_DANGER : Notification::SEVERITY_WARNING,
                    'title' => 'Probation ending soon',
                    'message' => "{$employee->full_name}'s probation ends in {$days} day(s).",
                    'action_url' => '/contracts',
                    'reminder_key' => $reminderKey,
                    'data' => [
                        'employee_id' => $employee->id,
                        'employee_name' => $employee->full_name,
                        'contract_end_date' => $employee->contract_end_date->format('Y-m-d'),
                        'days_remaining' => $days,
                    ],
                ]);
            });

        $this->markStaleRead($user, Notification::TYPE_PROBATION_ENDING, $activeKeys);
    }

    private function syncHrApprovalReminders(User $user, int $companyId): void
    {
        $activeKeys = [];

        LeaveRequest::query()
            ->with('employee')
            ->where('company_id', $companyId)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->where('supervisor_status', LeaveRequest::DECISION_APPROVED)
            ->where('hr_status', LeaveRequest::DECISION_PENDING)
            ->get()
            ->each(function (LeaveRequest $leaveRequest) use (&$activeKeys, $companyId, $user) {
                $reminderKey = "hr-leave-approval:{$leaveRequest->id}";
                $activeKeys[] = $reminderKey;

                $this->upsertReminder($user, [
                    'company_id' => $companyId,
                    'type' => Notification::TYPE_PENDING_APPROVAL,
                    'severity' => Notification::SEVERITY_INFO,
                    'title' => 'Leave approval pending',
                    'message' => "{$leaveRequest->employee->full_name}'s leave request is waiting for HR approval.",
                    'action_url' => '/leaves/approvals',
                    'reminder_key' => $reminderKey,
                    'data' => [
                        'leave_request_id' => $leaveRequest->id,
                        'employee_name' => $leaveRequest->employee->full_name,
                        'start_date' => $leaveRequest->start_date->format('Y-m-d'),
                        'end_date' => $leaveRequest->end_date->format('Y-m-d'),
                    ],
                ]);
            });

        $this->markStaleRead($user, Notification::TYPE_PENDING_APPROVAL, $activeKeys, 'hr-leave-approval:%');
    }

    private function syncSupervisorApprovalReminders(User $user): void
    {
        $supervisor = $user->employee;

        if (! $supervisor) {
            return;
        }

        $directReportIds = $supervisor->directReports()->pluck('id');

        $activeKeys = [];

        LeaveRequest::query()
            ->with('employee')
            ->where('company_id', $supervisor->company_id)
            ->whereIn('employee_id', $directReportIds)
            ->where('status', LeaveRequest::STATUS_PENDING)
            ->where('supervisor_status', LeaveRequest::DECISION_PENDING)
            ->get()
            ->each(function (LeaveRequest $leaveRequest) use (&$activeKeys, $supervisor, $user) {
                $reminderKey = "supervisor-leave-approval:{$leaveRequest->id}";
                $activeKeys[] = $reminderKey;

                $this->upsertReminder($user, [
                    'company_id' => $supervisor->company_id,
                    'type' => Notification::TYPE_PENDING_APPROVAL,
                    'severity' => Notification::SEVERITY_INFO,
                    'title' => 'Team leave pending',
                    'message' => "{$leaveRequest->employee->full_name}'s leave request is waiting for your approval.",
                    'action_url' => '/leaves/approvals',
                    'reminder_key' => $reminderKey,
                    'data' => [
                        'leave_request_id' => $leaveRequest->id,
                        'employee_name' => $leaveRequest->employee->full_name,
                        'start_date' => $leaveRequest->start_date->format('Y-m-d'),
                        'end_date' => $leaveRequest->end_date->format('Y-m-d'),
                    ],
                ]);
            });

        $this->markStaleRead($user, Notification::TYPE_PENDING_APPROVAL, $activeKeys, 'supervisor-leave-approval:%');
    }

    private function syncPayrollReminder(User $user, int $companyId): void
    {
        $activeEmployeeIds = Employee::query()
            ->where('company_id', $companyId)
            ->where('employment_status', Employee::STATUS_ACTIVE)
            ->pluck('id');
        $periodLabel = now()->format('Y-m');

        if ($activeEmployeeIds->isEmpty()) {
            $this->markStaleRead($user, Notification::TYPE_PAYROLL_ALERT, [], "payroll-missing:{$periodLabel}");

            return;
        }

        $generatedCount = Payroll::query()
            ->where('company_id', $companyId)
            ->whereIn('employee_id', $activeEmployeeIds)
            ->where('period_year', now()->year)
            ->where('period_month', now()->month)
            ->count();
        $missingCount = $activeEmployeeIds->count() - $generatedCount;

        if ($missingCount <= 0) {
            $this->markStaleRead($user, Notification::TYPE_PAYROLL_ALERT, [], "payroll-missing:{$periodLabel}");

            return;
        }

        $this->upsertReminder($user, [
            'company_id' => $companyId,
            'type' => Notification::TYPE_PAYROLL_ALERT,
            'severity' => now()->day >= 25 ? Notification::SEVERITY_DANGER : Notification::SEVERITY_WARNING,
            'title' => 'Payroll not fully generated',
            'message' => "{$missingCount} active employee(s) do not have payroll for {$periodLabel}.",
            'action_url' => '/payroll',
            'reminder_key' => "payroll-missing:{$periodLabel}",
            'data' => [
                'period_year' => now()->year,
                'period_month' => now()->month,
                'missing_count' => $missingCount,
                'active_employees' => $activeEmployeeIds->count(),
                'generated_count' => $generatedCount,
            ],
        ]);
    }

    /**
     * @param  list<string>  $activeKeys
     */
    private function markStaleRead(User $user, string $type, array $activeKeys, ?string $reminderPattern = null): void
    {
        Notification::query()
            ->forUser($user)
            ->where('type', $type)
            ->whereNull('read_at')
            ->when($reminderPattern, fn ($query) => $query->where('reminder_key', 'like', $reminderPattern))
            ->when(
                $activeKeys !== [],
                fn ($query) => $query->whereNotIn('reminder_key', $activeKeys)
            )
            ->update(['read_at' => now(), 'updated_at' => now()]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function upsertReminder(User $user, array $payload): Notification
    {
        $notification = Notification::query()->firstOrNew([
            'company_id' => $payload['company_id'],
            'user_id' => $user->id,
            'reminder_key' => $payload['reminder_key'],
        ]);

        $notification->fill([
            ...$payload,
            'user_id' => $user->id,
            'triggered_at' => $payload['triggered_at'] ?? now(),
        ]);

        if (! $notification->exists) {
            $notification->read_at = null;
        }

        $notification->save();

        return $notification;
    }

    private function ensureOwner(Notification $notification, User $user): void
    {
        if ((int) $notification->company_id !== $user->companyId() || (int) $notification->user_id !== $user->id) {
            abort(404);
        }
    }
}
