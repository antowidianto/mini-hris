<?php

namespace Tests\Feature\Attendance;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_employee_can_clock_in_and_clock_out_once_per_day(): void
    {
        $user = $this->employeeUser();
        Sanctum::actingAs($user);

        Carbon::setTestNow('2026-05-05 08:05:00');

        $this->postJson('/api/attendance/clock-in')
            ->assertCreated()
            ->assertJsonPath('message', 'Clock-in recorded successfully')
            ->assertJsonPath('data.attendance.attendance_date', '2026-05-05')
            ->assertJsonPath('data.attendance.time_in', '08:05:00')
            ->assertJsonPath('data.attendance.status', Attendance::STATUS_PRESENT)
            ->assertJsonPath('data.attendance.shift_start', '08:00:00')
            ->assertJsonPath('data.attendance.shift_end', '17:00:00')
            ->assertJsonPath('data.attendance.late_tolerance_minutes', 10)
            ->assertJsonPath('data.attendance.attendance_source', Attendance::SOURCE_MANUAL);

        $this->postJson('/api/attendance/clock-in')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attendance_date'], 'errors');

        Carbon::setTestNow('2026-05-05 17:30:00');

        $this->postJson('/api/attendance/clock-out')
            ->assertOk()
            ->assertJsonPath('message', 'Clock-out recorded successfully')
            ->assertJsonPath('data.attendance.time_out', '17:30:00')
            ->assertJsonPath('data.attendance.overtime_minutes', 30);

        $this->postJson('/api/attendance/clock-out')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['time_out'], 'errors');
    }

    public function test_late_clock_in_is_marked_late(): void
    {
        $user = $this->employeeUser();
        Sanctum::actingAs($user);
        Carbon::setTestNow('2026-05-05 08:15:00');

        $this->postJson('/api/attendance/clock-in')
            ->assertCreated()
            ->assertJsonPath('data.attendance.status', Attendance::STATUS_LATE);
    }

    public function test_clock_out_requires_clock_in(): void
    {
        Sanctum::actingAs($this->employeeUser());
        Carbon::setTestNow('2026-05-05 17:00:00');

        $this->postJson('/api/attendance/clock-out')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['attendance_date'], 'errors');
    }

    public function test_clock_out_requires_a_clock_in_time(): void
    {
        $user = $this->employeeUser();
        Sanctum::actingAs($user);
        Carbon::setTestNow('2026-05-05 17:00:00');

        Attendance::factory()->for($user->employee)->create([
            'attendance_date' => '2026-05-05',
            'time_in' => null,
            'time_out' => null,
            'status' => Attendance::STATUS_ABSENT,
        ]);

        $this->postJson('/api/attendance/clock-out')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['time_in'], 'errors');
    }

    public function test_employee_can_view_personal_attendance_history(): void
    {
        $user = $this->employeeUser();
        $employee = $user->employee;
        Sanctum::actingAs($user);
        Carbon::setTestNow('2026-05-05 08:30:00');

        Attendance::factory()->for($employee)->create([
            'attendance_date' => '2026-05-04',
            'time_in' => '08:40:00',
            'time_out' => '17:20:00',
            'status' => Attendance::STATUS_PRESENT,
        ]);
        Attendance::factory()->for($employee)->create([
            'attendance_date' => '2026-05-05',
            'time_in' => '08:30:00',
            'time_out' => null,
            'status' => Attendance::STATUS_PRESENT,
        ]);

        $this->getJson('/api/attendance/my?per_page=5')
            ->assertOk()
            ->assertJsonPath('data.today.attendance_date', '2026-05-05')
            ->assertJsonCount(2, 'data.attendances')
            ->assertJsonPath('data.meta.per_page', 5);
    }

    public function test_employee_attendance_history_returns_null_today_when_no_record_exists(): void
    {
        $user = $this->employeeUser();
        Sanctum::actingAs($user);
        Carbon::setTestNow('2026-05-05 08:30:00');

        $this->getJson('/api/attendance/my')
            ->assertOk()
            ->assertJsonPath('data.today', null);
    }

    public function test_admin_can_view_attendance_report_with_filters(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $department = Department::factory()->create(['name' => 'Operations']);
        $position = Position::factory()->for($department)->create();
        $employee = Employee::factory()->for($department)->for($position)->create([
            'full_name' => 'Report Employee',
        ]);
        Attendance::factory()->for($employee)->create([
            'attendance_date' => '2026-05-05',
            'status' => Attendance::STATUS_LATE,
            'time_in' => '09:30:00',
            'attendance_source' => Attendance::SOURCE_FINGERPRINT,
        ]);

        $this->getJson('/api/attendance/report?department_id='.$department->id.'&status=late&attendance_source=fingerprint&date_from=2026-05-01&date_to=2026-05-31')
            ->assertOk()
            ->assertJsonPath('data.attendances.0.employee.full_name', 'Report Employee')
            ->assertJsonPath('data.attendances.0.status', Attendance::STATUS_LATE)
            ->assertJsonPath('data.attendances.0.attendance_source', Attendance::SOURCE_FINGERPRINT);
    }

    public function test_admin_can_view_monthly_attendance_recap(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $department = Department::factory()->create(['name' => 'Operations']);
        $position = Position::factory()->for($department)->create();
        $employee = Employee::factory()->for($department)->for($position)->create([
            'employee_id' => 'EMP-RECAP-1',
            'full_name' => 'Recap Employee',
        ]);

        Attendance::factory()->for($employee)->create([
            'attendance_date' => '2026-05-01',
            'status' => Attendance::STATUS_PRESENT,
            'overtime_minutes' => 30,
        ]);
        Attendance::factory()->for($employee)->create([
            'attendance_date' => '2026-05-02',
            'status' => Attendance::STATUS_LATE,
            'overtime_minutes' => 15,
        ]);
        Attendance::factory()->for($employee)->create([
            'attendance_date' => '2026-05-03',
            'status' => Attendance::STATUS_SICK,
            'time_in' => null,
            'time_out' => null,
            'overtime_minutes' => 0,
        ]);
        Attendance::factory()->for($employee)->create([
            'attendance_date' => '2026-05-04',
            'status' => Attendance::STATUS_PERMISSION,
            'time_in' => null,
            'time_out' => null,
            'overtime_minutes' => 0,
        ]);
        Attendance::factory()->for($employee)->create([
            'attendance_date' => '2026-05-05',
            'status' => Attendance::STATUS_ALPHA,
            'time_in' => null,
            'time_out' => null,
            'overtime_minutes' => 0,
        ]);

        $this->getJson('/api/attendance/monthly-recap?year=2026&month=5&department_id='.$department->id)
            ->assertOk()
            ->assertJsonPath('data.recap.0.employee.employee_id', 'EMP-RECAP-1')
            ->assertJsonPath('data.recap.0.present_days', 1)
            ->assertJsonPath('data.recap.0.late_days', 1)
            ->assertJsonPath('data.recap.0.sick_days', 1)
            ->assertJsonPath('data.recap.0.permission_days', 1)
            ->assertJsonPath('data.recap.0.alpha_days', 1)
            ->assertJsonPath('data.recap.0.overtime_minutes', 45);
    }

    public function test_attendance_import_placeholder_accepts_fingerprint_file_metadata(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_HR]));

        $this->postJson('/api/attendance/import-placeholder', [
            'file_name' => 'fingerprint-may-2026.csv',
            'source' => Attendance::SOURCE_FINGERPRINT,
            'notes' => 'Preview only.',
        ])
            ->assertAccepted()
            ->assertJsonPath('message', 'Attendance import placeholder accepted')
            ->assertJsonPath('data.import.file_name', 'fingerprint-may-2026.csv')
            ->assertJsonPath('data.import.status', 'placeholder');
    }

    public function test_roles_are_restricted_for_attendance_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_EMPLOYEE]));

        $this->getJson('/api/attendance/report')
            ->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_HR]));

        $this->postJson('/api/attendance/clock-in')
            ->assertForbidden();
    }

    public function test_active_employee_profile_is_required_to_clock_in(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_EMPLOYEE]));

        $this->postJson('/api/attendance/clock-in')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['employee'], 'errors');
    }

    private function employeeUser(): User
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();

        Employee::factory()
            ->for($user)
            ->for($department)
            ->for($position)
            ->create([
                'employment_status' => Employee::STATUS_ACTIVE,
            ]);

        return $user->refresh()->load('employee');
    }
}
