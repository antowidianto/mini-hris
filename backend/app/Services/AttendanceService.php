<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function clockIn(User $user): Attendance
    {
        $employee = $this->employeeForUser($user);
        $settings = CompanySetting::query()->firstOrCreate(['id' => 1], CompanySetting::defaults());
        $now = now();

        return DB::transaction(function () use ($employee, $now, $settings) {
            $existingAttendance = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $now->toDateString())
                ->lockForUpdate()
                ->first();

            if ($existingAttendance) {
                throw ValidationException::withMessages([
                    'attendance_date' => ['You have already clocked in today.'],
                ]);
            }

            return Attendance::query()->create([
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'attendance_date' => $now->toDateString(),
                'shift_start' => $settings->default_work_start,
                'shift_end' => $settings->default_work_end,
                'late_tolerance_minutes' => $settings->late_tolerance_minutes,
                'time_in' => $now->format('H:i:s'),
                'time_out' => null,
                'overtime_minutes' => 0,
                'status' => $this->statusForClockIn($now, $settings->default_work_start, $settings->late_tolerance_minutes),
                'attendance_source' => Attendance::SOURCE_MANUAL,
            ])->load(['employee.department', 'employee.position']);
        });
    }

    public function clockOut(User $user): Attendance
    {
        $employee = $this->employeeForUser($user);
        $now = now();

        return DB::transaction(function () use ($employee, $now) {
            /** @var Attendance|null $attendance */
            $attendance = Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('attendance_date', $now->toDateString())
                ->lockForUpdate()
                ->first();

            if (! $attendance) {
                throw ValidationException::withMessages([
                    'attendance_date' => ['Clock in is required before clock out.'],
                ]);
            }

            if ($attendance->time_out) {
                throw ValidationException::withMessages([
                    'time_out' => ['You have already clocked out today.'],
                ]);
            }

            if (! $attendance->time_in) {
                throw ValidationException::withMessages([
                    'time_in' => ['Clock in is required before clock out.'],
                ]);
            }

            $timeIn = Carbon::parse($attendance->attendance_date->format('Y-m-d').' '.$attendance->time_in);

            if ($now->lessThan($timeIn)) {
                throw ValidationException::withMessages([
                    'time_out' => ['Clock out cannot happen before clock in.'],
                ]);
            }

            $attendance->update([
                'time_out' => $now->format('H:i:s'),
                'overtime_minutes' => $this->overtimeMinutes($attendance, $now),
            ]);

            return $attendance->refresh()->load(['employee.department', 'employee.position']);
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function myAttendance(User $user, array $filters): LengthAwarePaginator
    {
        $employee = $this->employeeForUser($user);
        $perPage = min((int) ($filters['per_page'] ?? 10), 50);

        return Attendance::query()
            ->with(['employee.department', 'employee.position'])
            ->where('employee_id', $employee->id)
            ->betweenDates($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->latest('attendance_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function todayFor(User $user): ?Attendance
    {
        $employee = $this->employeeForUser($user);

        return Attendance::query()
            ->with(['employee.department', 'employee.position'])
            ->where('employee_id', $employee->id)
            ->whereDate('attendance_date', now()->toDateString())
            ->first();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function report(array $filters, User $actor): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 10), 50);

        return Attendance::query()
            ->with(['employee.department', 'employee.position'])
            ->where('company_id', $actor->companyId())
            ->betweenDates($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['department_id'] ?? null, function ($query, $departmentId) {
                $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('department_id', $departmentId));
            })
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($filters['attendance_source'] ?? null, fn ($query, $source) => $query->where('attendance_source', $source))
            ->latest('attendance_date')
            ->latest('time_in')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<int, array<string, mixed>>
     */
    public function monthlyRecap(array $filters, User $actor): array
    {
        $year = (int) ($filters['year'] ?? now()->year);
        $month = (int) ($filters['month'] ?? now()->month);

        $attendances = Attendance::query()
            ->with(['employee.department', 'employee.position'])
            ->where('company_id', $actor->companyId())
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId))
            ->when($filters['department_id'] ?? null, function ($query, $departmentId) {
                $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('department_id', $departmentId));
            })
            ->orderBy('attendance_date')
            ->get();

        return $attendances
            ->groupBy('employee_id')
            ->map(function ($records) {
                /** @var Attendance $first */
                $first = $records->first();

                return [
                    'employee' => [
                        'id' => $first->employee->id,
                        'employee_id' => $first->employee->employee_id,
                        'full_name' => $first->employee->full_name,
                        'department' => $first->employee->department?->name,
                        'position' => $first->employee->position?->name,
                    ],
                    'present_days' => $records->where('status', Attendance::STATUS_PRESENT)->count(),
                    'late_days' => $records->where('status', Attendance::STATUS_LATE)->count(),
                    'sick_days' => $records->where('status', Attendance::STATUS_SICK)->count(),
                    'permission_days' => $records->where('status', Attendance::STATUS_PERMISSION)->count(),
                    'leave_days' => $records->where('status', Attendance::STATUS_LEAVE)->count(),
                    'alpha_days' => $records->whereIn('status', [Attendance::STATUS_ALPHA, Attendance::STATUS_ABSENT])->count(),
                    'overtime_minutes' => $records->sum('overtime_minutes'),
                    'total_records' => $records->count(),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public function importPlaceholder(array $data, User $user): array
    {
        return [
            'file_name' => $data['file_name'],
            'source' => $data['source'],
            'requested_by' => $user->email,
            'status' => 'placeholder',
            'message' => 'CSV/Excel fingerprint attendance import parser is not enabled yet.',
        ];
    }

    private function employeeForUser(User $user): Employee
    {
        $employee = $user->employee()->where('employment_status', Employee::STATUS_ACTIVE)->first();

        if (! $employee) {
            throw ValidationException::withMessages([
                'employee' => ['An active employee profile is required for attendance.'],
            ]);
        }

        return $employee;
    }

    private function statusForClockIn(Carbon $clockInTime, string $shiftStart, int $lateToleranceMinutes): string
    {
        $lateAfter = Carbon::parse($clockInTime->toDateString().' '.$shiftStart)->addMinutes($lateToleranceMinutes);

        return $clockInTime->greaterThan($lateAfter)
            ? Attendance::STATUS_LATE
            : Attendance::STATUS_PRESENT;
    }

    private function overtimeMinutes(Attendance $attendance, Carbon $clockOutTime): int
    {
        if (! $attendance->shift_end) {
            return 0;
        }

        $shiftEnd = Carbon::parse($attendance->attendance_date->format('Y-m-d').' '.$attendance->shift_end);

        if ($clockOutTime->lessThanOrEqualTo($shiftEnd)) {
            return 0;
        }

        return (int) $shiftEnd->diffInMinutes($clockOutTime);
    }

    public function overtimeMinutesForValues(string $attendanceDate, string $timeOut, int $companyId): int
    {
        $settings = CompanySetting::query()->firstOrCreate(['company_id' => $companyId], CompanySetting::defaults());
        $shiftEnd = Carbon::parse($attendanceDate.' '.$settings->default_work_end);
        $clockOut = Carbon::parse($attendanceDate.' '.$timeOut);

        if ($clockOut->lessThanOrEqualTo($shiftEnd)) {
            return 0;
        }

        return (int) $shiftEnd->diffInMinutes($clockOut);
    }
}
