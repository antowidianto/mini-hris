<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class ReportingService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    public function operational(array $filters, User $actor): array
    {
        [$dateFrom, $dateTo] = $this->dateRange($filters);
        $companyId = $actor->companyId();

        $employees = Employee::query()
            ->with(['branch', 'department', 'position'])
            ->where('company_id', $companyId)
            ->tap(fn (Builder $query) => $this->applyEmployeeFilters($query, $filters))
            ->orderBy('full_name')
            ->get();

        $employeeIds = $employees->pluck('id');
        $employeesById = $employees->keyBy('id');

        $attendances = Attendance::query()
            ->where('company_id', $companyId)
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('attendance_date', [$dateFrom, $dateTo])
            ->orderBy('attendance_date')
            ->get();

        $leaveRequests = LeaveRequest::query()
            ->with('leaveType')
            ->where('company_id', $companyId)
            ->whereIn('employee_id', $employeeIds)
            ->where('start_date', '<=', $dateTo)
            ->where('end_date', '>=', $dateFrom)
            ->orderBy('start_date')
            ->get();

        return [
            'filters' => [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'branch_id' => $filters['branch_id'] ?? null,
                'department_id' => $filters['department_id'] ?? null,
                'employment_status' => $filters['employment_status'] ?? null,
            ],
            'summary' => $this->summary($employees, $attendances, $leaveRequests, $dateFrom, $dateTo),
            'attendance_recap' => $this->attendanceRecap($employees, $attendances),
            'late_report' => $this->lateReport($attendances, $employeesById),
            'overtime_report' => $this->overtimeReport($attendances, $employeesById),
            'leave_report' => $this->leaveReport($leaveRequests, $employeesById, $dateFrom, $dateTo),
            'headcount_by_branch' => $this->headcountByBranch($employees),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{0: string, 1: string}
     */
    private function dateRange(array $filters): array
    {
        $dateFrom = isset($filters['date_from'])
            ? Carbon::parse($filters['date_from'])->toDateString()
            : now()->startOfMonth()->toDateString();
        $dateTo = isset($filters['date_to'])
            ? Carbon::parse($filters['date_to'])->toDateString()
            : now()->toDateString();

        return [$dateFrom, $dateTo];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function applyEmployeeFilters(Builder $query, array $filters): void
    {
        $query
            ->when($filters['branch_id'] ?? null, fn (Builder $query, int $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['department_id'] ?? null, fn (Builder $query, int $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['employment_status'] ?? null, fn (Builder $query, string $status) => $query->where('employment_status', $status));
    }

    /**
     * @return array<string, int>
     */
    private function summary($employees, $attendances, $leaveRequests, string $dateFrom, string $dateTo): array
    {
        return [
            'headcount' => $employees->count(),
            'active_headcount' => $employees->where('employment_status', Employee::STATUS_ACTIVE)->count(),
            'inactive_headcount' => $employees->where('employment_status', Employee::STATUS_INACTIVE)->count(),
            'attendance_records' => $attendances->count(),
            'late_days' => $attendances->where('status', Attendance::STATUS_LATE)->count(),
            'overtime_minutes' => (int) $attendances->sum('overtime_minutes'),
            'approved_leave_days' => (int) $leaveRequests
                ->where('status', LeaveRequest::STATUS_APPROVED)
                ->sum(fn (LeaveRequest $leaveRequest) => $this->overlapDays($leaveRequest, $dateFrom, $dateTo)),
            'pending_leave_requests' => $leaveRequests->where('status', LeaveRequest::STATUS_PENDING)->count(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function attendanceRecap($employees, $attendances): array
    {
        $recordsByEmployee = $attendances->groupBy('employee_id');

        return $employees
            ->map(function (Employee $employee) use ($recordsByEmployee) {
                $records = $recordsByEmployee->get($employee->id, collect());

                return [
                    'employee' => $this->employeePayload($employee),
                    'present_days' => $records->where('status', Attendance::STATUS_PRESENT)->count(),
                    'late_days' => $records->where('status', Attendance::STATUS_LATE)->count(),
                    'sick_days' => $records->where('status', Attendance::STATUS_SICK)->count(),
                    'permission_days' => $records->where('status', Attendance::STATUS_PERMISSION)->count(),
                    'leave_days' => $records->where('status', Attendance::STATUS_LEAVE)->count(),
                    'alpha_days' => $records->whereIn('status', [Attendance::STATUS_ABSENT, Attendance::STATUS_ALPHA])->count(),
                    'overtime_minutes' => (int) $records->sum('overtime_minutes'),
                    'total_records' => $records->count(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function lateReport($attendances, $employeesById): array
    {
        return $attendances
            ->where('status', Attendance::STATUS_LATE)
            ->sortByDesc('attendance_date')
            ->take(100)
            ->map(fn (Attendance $attendance) => [
                'id' => $attendance->id,
                'employee' => $this->employeePayload($employeesById->get($attendance->employee_id)),
                'attendance_date' => $attendance->attendance_date?->format('Y-m-d'),
                'time_in' => $attendance->time_in,
                'shift_start' => $attendance->shift_start,
                'late_minutes' => $this->lateMinutes($attendance),
                'source' => $attendance->attendance_source,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function overtimeReport($attendances, $employeesById): array
    {
        return $attendances
            ->where('overtime_minutes', '>', 0)
            ->sortByDesc('overtime_minutes')
            ->take(100)
            ->map(fn (Attendance $attendance) => [
                'id' => $attendance->id,
                'employee' => $this->employeePayload($employeesById->get($attendance->employee_id)),
                'attendance_date' => $attendance->attendance_date?->format('Y-m-d'),
                'time_out' => $attendance->time_out,
                'shift_end' => $attendance->shift_end,
                'overtime_minutes' => (int) $attendance->overtime_minutes,
                'source' => $attendance->attendance_source,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function leaveReport($leaveRequests, $employeesById, string $dateFrom, string $dateTo): array
    {
        return [
            'summary_by_type' => $leaveRequests
                ->groupBy('leave_type_id')
                ->map(function ($requests) use ($dateFrom, $dateTo) {
                    /** @var LeaveRequest $first */
                    $first = $requests->first();

                    return [
                        'leave_type' => $first->leaveType?->name ?? 'Unknown',
                        'request_count' => $requests->count(),
                        'approved_days' => (int) $requests
                            ->where('status', LeaveRequest::STATUS_APPROVED)
                            ->sum(fn (LeaveRequest $leaveRequest) => $this->overlapDays($leaveRequest, $dateFrom, $dateTo)),
                        'pending_days' => (int) $requests
                            ->where('status', LeaveRequest::STATUS_PENDING)
                            ->sum(fn (LeaveRequest $leaveRequest) => $this->overlapDays($leaveRequest, $dateFrom, $dateTo)),
                        'rejected_days' => (int) $requests
                            ->where('status', LeaveRequest::STATUS_REJECTED)
                            ->sum(fn (LeaveRequest $leaveRequest) => $this->overlapDays($leaveRequest, $dateFrom, $dateTo)),
                    ];
                })
                ->values()
                ->all(),
            'requests' => $leaveRequests
                ->take(100)
                ->map(function (LeaveRequest $leaveRequest) use ($employeesById, $dateFrom, $dateTo) {
                    return [
                        'id' => $leaveRequest->id,
                        'employee' => $this->employeePayload($employeesById->get($leaveRequest->employee_id)),
                        'leave_type' => $leaveRequest->leaveType?->name,
                        'start_date' => $leaveRequest->start_date?->format('Y-m-d'),
                        'end_date' => $leaveRequest->end_date?->format('Y-m-d'),
                        'total_days' => $leaveRequest->total_days,
                        'report_days' => $this->overlapDays($leaveRequest, $dateFrom, $dateTo),
                        'status' => $leaveRequest->status,
                    ];
                })
                ->values()
                ->all(),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function headcountByBranch($employees): array
    {
        return $employees
            ->groupBy(fn (Employee $employee) => $employee->branch_id ?: 'unassigned')
            ->map(function ($branchEmployees) {
                /** @var Employee $first */
                $first = $branchEmployees->first();

                return [
                    'branch' => [
                        'id' => $first->branch?->id,
                        'name' => $first->branch?->name ?? 'Unassigned',
                        'code' => $first->branch?->code,
                    ],
                    'total' => $branchEmployees->count(),
                    'active' => $branchEmployees->where('employment_status', Employee::STATUS_ACTIVE)->count(),
                    'inactive' => $branchEmployees->where('employment_status', Employee::STATUS_INACTIVE)->count(),
                    'departments' => $branchEmployees
                        ->groupBy(fn (Employee $employee) => $employee->department?->name ?? 'Unassigned')
                        ->map(fn ($departmentEmployees, string $department) => [
                            'department' => $department,
                            'count' => $departmentEmployees->count(),
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->sortBy('branch.name')
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function employeePayload(?Employee $employee): array
    {
        return [
            'id' => $employee?->id,
            'employee_id' => $employee?->employee_id,
            'full_name' => $employee?->full_name,
            'employment_status' => $employee?->employment_status,
            'branch' => $employee?->branch?->name,
            'department' => $employee?->department?->name,
            'position' => $employee?->position?->name,
        ];
    }

    private function lateMinutes(Attendance $attendance): int
    {
        if (! $attendance->time_in || ! $attendance->shift_start) {
            return 0;
        }

        $attendanceDate = $attendance->attendance_date?->format('Y-m-d');

        if (! $attendanceDate) {
            return 0;
        }

        $lateAfter = Carbon::parse($attendanceDate.' '.$attendance->shift_start)
            ->addMinutes((int) $attendance->late_tolerance_minutes);
        $timeIn = Carbon::parse($attendanceDate.' '.$attendance->time_in);

        if ($timeIn->lessThanOrEqualTo($lateAfter)) {
            return 0;
        }

        return (int) $lateAfter->diffInMinutes($timeIn);
    }

    private function overlapDays(LeaveRequest $leaveRequest, string $dateFrom, string $dateTo): int
    {
        $startDate = $leaveRequest->start_date->greaterThan(Carbon::parse($dateFrom))
            ? $leaveRequest->start_date
            : Carbon::parse($dateFrom);
        $endDate = $leaveRequest->end_date->lessThan(Carbon::parse($dateTo))
            ? $leaveRequest->end_date
            : Carbon::parse($dateTo);

        if ($startDate->greaterThan($endDate)) {
            return 0;
        }

        return (int) $startDate->diffInDays($endDate) + 1;
    }
}
