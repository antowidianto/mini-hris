<?php

namespace App\Services;

use App\Http\Resources\AttendanceResource;
use App\Http\Resources\ContractExpiryResource;
use App\Http\Resources\LeaveRequestResource;
use App\Http\Resources\PayrollResource;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\User;

class DashboardService
{
    public function __construct(private readonly ContractService $contractService) {}

    /**
     * @return array<string, mixed>
     */
    public function forUser(User $user): array
    {
        if ($user->hasRole([User::ROLE_ADMIN, User::ROLE_HR])) {
            return [
                'role' => $user->role,
                'metrics' => $this->adminMetrics($user),
            ];
        }

        return [
            'role' => $user->role,
            'metrics' => $this->employeeMetrics($user),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function adminMetrics(User $user): array
    {
        $activeEmployeeIds = Employee::query()
            ->where('company_id', $user->companyId())
            ->where('employment_status', Employee::STATUS_ACTIVE)
            ->pluck('id');
        $activeEmployees = $activeEmployeeIds->count();
        $contractExpiringCounts = $this->contractService->expiringCounts($user);
        $payrollGeneratedThisMonth = Payroll::query()
            ->whereIn('employee_id', $activeEmployeeIds)
            ->where('period_year', now()->year)
            ->where('period_month', now()->month)
            ->count();

        return [
            'total_employees' => Employee::query()->where('company_id', $user->companyId())->count(),
            'active_employees' => $activeEmployees,
            'attendance_today' => Attendance::query()
                ->whereIn('employee_id', $activeEmployeeIds)
                ->whereDate('attendance_date', now()->toDateString())
                ->count(),
            'attendance_today_breakdown' => $this->attendanceTodayBreakdown($activeEmployeeIds->all()),
            'pending_leave_requests' => LeaveRequest::query()
                ->whereIn('employee_id', $activeEmployeeIds)
                ->where('status', LeaveRequest::STATUS_PENDING)
                ->count(),
            'pending_supervisor_approvals' => LeaveRequest::query()
                ->whereIn('employee_id', $activeEmployeeIds)
                ->where('status', LeaveRequest::STATUS_PENDING)
                ->where('supervisor_status', LeaveRequest::DECISION_PENDING)
                ->count(),
            'pending_hr_approvals' => LeaveRequest::query()
                ->whereIn('employee_id', $activeEmployeeIds)
                ->where('status', LeaveRequest::STATUS_PENDING)
                ->where('supervisor_status', LeaveRequest::DECISION_APPROVED)
                ->where('hr_status', LeaveRequest::DECISION_PENDING)
                ->count(),
            'payroll_generated_this_month' => $payrollGeneratedThisMonth,
            'payroll_readiness' => $this->payrollReadiness($activeEmployees, $payrollGeneratedThisMonth),
            'contract_expiry' => [
                ...$contractExpiringCounts,
                'preview' => ContractExpiryResource::collection($this->contractService->expiringPreview($user, 60, 5)),
            ],
            ...$contractExpiringCounts,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function employeeMetrics(User $user): array
    {
        $employee = $user->employee;

        if (! $employee) {
            return [
                'has_employee_profile' => false,
                'attendance_today' => null,
                'remaining_leave_balance' => 0,
                'latest_leave_request' => null,
                'latest_payslip' => null,
                'pending_supervisor_approvals' => 0,
            ];
        }

        $attendanceToday = Attendance::query()
            ->with(['employee.department', 'employee.position'])
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', now()->toDateString())
            ->first();

        $latestLeaveRequest = LeaveRequest::query()
            ->with(['employee.department', 'employee.position', 'leaveType', 'approver'])
            ->where('employee_id', $employee->id)
            ->latest()
            ->first();

        $latestPayslip = Payroll::query()
            ->with(['employee.department', 'employee.position', 'generator'])
            ->where('employee_id', $employee->id)
            ->latest('period_year')
            ->latest('period_month')
            ->latest()
            ->first();

        return [
            'has_employee_profile' => true,
            'attendance_today' => $attendanceToday ? new AttendanceResource($attendanceToday) : null,
            'remaining_leave_balance' => LeaveBalance::query()
                ->where('employee_id', $employee->id)
                ->where('year', now()->year)
                ->get()
                ->sum(fn (LeaveBalance $balance) => $balance->remainingDays()),
            'latest_leave_request' => $latestLeaveRequest ? new LeaveRequestResource($latestLeaveRequest) : null,
            'latest_payslip' => $latestPayslip ? new PayrollResource($latestPayslip) : null,
            'pending_supervisor_approvals' => LeaveRequest::query()
                ->whereIn('employee_id', $employee->directReports()
                    ->where('employment_status', Employee::STATUS_ACTIVE)
                    ->pluck('id'))
                ->where('status', LeaveRequest::STATUS_PENDING)
                ->where('supervisor_status', LeaveRequest::DECISION_PENDING)
                ->count(),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function attendanceTodayBreakdown(array $activeEmployeeIds): array
    {
        $activeEmployees = count($activeEmployeeIds);
        $records = Attendance::query()
            ->whereIn('employee_id', $activeEmployeeIds)
            ->whereDate('attendance_date', now()->toDateString())
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');
        $recorded = (int) $records->sum();

        return [
            'active_employees' => $activeEmployees,
            'recorded' => $recorded,
            'present' => (int) ($records[Attendance::STATUS_PRESENT] ?? 0),
            'late' => (int) ($records[Attendance::STATUS_LATE] ?? 0),
            'leave' => (int) ($records[Attendance::STATUS_LEAVE] ?? 0),
            'sick' => (int) ($records[Attendance::STATUS_SICK] ?? 0),
            'permission' => (int) ($records[Attendance::STATUS_PERMISSION] ?? 0),
            'absent' => (int) ($records[Attendance::STATUS_ABSENT] ?? 0),
            'alpha' => (int) ($records[Attendance::STATUS_ALPHA] ?? 0),
            'not_recorded' => max(0, $activeEmployees - $recorded),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payrollReadiness(int $activeEmployees, int $generatedCount): array
    {
        $missingCount = max(0, $activeEmployees - $generatedCount);

        return [
            'period_year' => now()->year,
            'period_month' => now()->month,
            'period_label' => now()->format('Y-m'),
            'active_employees' => $activeEmployees,
            'generated_count' => $generatedCount,
            'missing_count' => $missingCount,
            'completion_percent' => $activeEmployees > 0 ? round(($generatedCount / $activeEmployees) * 100, 2) : 100,
            'is_ready' => $activeEmployees > 0 && $missingCount === 0,
        ];
    }
}
