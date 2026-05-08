<?php

namespace Tests\Feature\Reports;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ReportingSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_view_operational_reports_with_filters(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $branch = Branch::factory()->create([
            'company_id' => $hr->company_id,
            'name' => 'Jakarta Branch',
            'code' => 'JKT',
        ]);
        $otherBranch = Branch::factory()->create(['company_id' => $hr->company_id]);
        $department = Department::factory()->create([
            'company_id' => $hr->company_id,
            'name' => 'Operations',
        ]);
        $position = Position::factory()->for($department)->create(['company_id' => $hr->company_id]);
        $employee = Employee::factory()
            ->for($branch)
            ->for($department)
            ->for($position)
            ->create([
                'company_id' => $hr->company_id,
                'employee_id' => 'EMP-RPT-1',
                'full_name' => 'Report Employee',
                'employment_status' => Employee::STATUS_ACTIVE,
            ]);
        $otherEmployee = Employee::factory()
            ->for($otherBranch)
            ->for($department)
            ->for($position)
            ->create(['company_id' => $hr->company_id]);
        $leaveType = LeaveType::factory()->create([
            'company_id' => $hr->company_id,
            'name' => 'Annual Leave',
        ]);

        Attendance::factory()->for($employee)->create([
            'company_id' => $hr->company_id,
            'attendance_date' => '2026-05-02',
            'status' => Attendance::STATUS_LATE,
            'shift_start' => '08:00:00',
            'late_tolerance_minutes' => 10,
            'time_in' => '08:40:00',
            'overtime_minutes' => 15,
        ]);
        Attendance::factory()->for($employee)->create([
            'company_id' => $hr->company_id,
            'attendance_date' => '2026-05-03',
            'status' => Attendance::STATUS_PRESENT,
            'overtime_minutes' => 45,
        ]);
        Attendance::factory()->for($otherEmployee)->create([
            'company_id' => $hr->company_id,
            'attendance_date' => '2026-05-04',
            'status' => Attendance::STATUS_LATE,
            'overtime_minutes' => 120,
        ]);
        LeaveRequest::factory()->for($employee)->for($leaveType)->create([
            'company_id' => $hr->company_id,
            'start_date' => '2026-05-06',
            'end_date' => '2026-05-07',
            'total_days' => 2,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);
        LeaveRequest::factory()->for($employee)->for($leaveType)->create([
            'company_id' => $hr->company_id,
            'start_date' => '2026-04-30',
            'end_date' => '2026-05-02',
            'total_days' => 3,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        Sanctum::actingAs($hr);

        $this->getJson("/api/reports/operational?date_from=2026-05-01&date_to=2026-05-31&branch_id={$branch->id}&employment_status=active")
            ->assertOk()
            ->assertJsonPath('data.report.summary.headcount', 1)
            ->assertJsonPath('data.report.summary.late_days', 1)
            ->assertJsonPath('data.report.summary.overtime_minutes', 60)
            ->assertJsonPath('data.report.summary.approved_leave_days', 4)
            ->assertJsonPath('data.report.attendance_recap.0.employee.employee_id', 'EMP-RPT-1')
            ->assertJsonPath('data.report.attendance_recap.0.present_days', 1)
            ->assertJsonPath('data.report.attendance_recap.0.late_days', 1)
            ->assertJsonPath('data.report.late_report.0.late_minutes', 30)
            ->assertJsonPath('data.report.overtime_report.0.overtime_minutes', 45)
            ->assertJsonPath('data.report.leave_report.summary_by_type.0.leave_type', 'Annual Leave')
            ->assertJsonPath('data.report.leave_report.summary_by_type.0.approved_days', 4)
            ->assertJsonPath('data.report.headcount_by_branch.0.branch.name', 'Jakarta Branch')
            ->assertJsonFragment(['report_days' => 2])
            ->assertJsonMissing(['employee_id' => $otherEmployee->employee_id]);
    }

    public function test_report_filters_must_belong_to_actor_company(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $otherCompany = Company::factory()->create();
        $otherBranch = Branch::factory()->create(['company_id' => $otherCompany->id]);
        $otherDepartment = Department::factory()->create(['company_id' => $otherCompany->id]);

        Sanctum::actingAs($hr);

        $this->getJson("/api/reports/operational?branch_id={$otherBranch->id}&department_id={$otherDepartment->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['branch_id', 'department_id'], 'errors');
    }

    public function test_employee_cannot_view_operational_reports(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_EMPLOYEE]));

        $this->getJson('/api/reports/operational')
            ->assertForbidden();
    }
}
