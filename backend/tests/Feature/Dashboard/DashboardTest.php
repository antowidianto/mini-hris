<?php

namespace Tests\Feature\Dashboard;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_admin_dashboard_returns_workforce_metrics(): void
    {
        Carbon::setTestNow('2026-05-05 10:00:00');
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $activeEmployee = $this->employeeWithUser();
        $inactiveEmployee = Employee::factory()->create(['employment_status' => Employee::STATUS_INACTIVE]);
        Attendance::factory()->for($activeEmployee)->create([
            'attendance_date' => '2026-05-05',
            'status' => Attendance::STATUS_PRESENT,
        ]);
        Attendance::factory()->for($inactiveEmployee)->create([
            'attendance_date' => '2026-05-05',
            'status' => Attendance::STATUS_PRESENT,
        ]);
        LeaveRequest::factory()->for($activeEmployee)->create([
            'status' => LeaveRequest::STATUS_PENDING,
            'supervisor_status' => LeaveRequest::DECISION_APPROVED,
            'hr_status' => LeaveRequest::DECISION_PENDING,
        ]);
        LeaveRequest::factory()->for($activeEmployee)->create([
            'status' => LeaveRequest::STATUS_PENDING,
            'supervisor_status' => LeaveRequest::DECISION_PENDING,
            'hr_status' => LeaveRequest::DECISION_PENDING,
        ]);
        LeaveRequest::factory()->for($inactiveEmployee)->create([
            'status' => LeaveRequest::STATUS_PENDING,
            'supervisor_status' => LeaveRequest::DECISION_PENDING,
        ]);
        Payroll::factory()->for($activeEmployee)->create([
            'period_year' => 2026,
            'period_month' => 5,
        ]);
        Employee::factory()->create([
            'employment_status' => Employee::STATUS_ACTIVE,
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWT,
            'contract_end_date' => '2026-05-25',
        ]);
        Employee::factory()->create([
            'employment_status' => Employee::STATUS_ACTIVE,
            'employment_type' => Employee::EMPLOYMENT_TYPE_PROBATION,
            'contract_end_date' => '2026-06-20',
        ]);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.role', User::ROLE_ADMIN)
            ->assertJsonPath('data.metrics.total_employees', 4)
            ->assertJsonPath('data.metrics.active_employees', 3)
            ->assertJsonPath('data.metrics.attendance_today', 1)
            ->assertJsonPath('data.metrics.attendance_today_breakdown.present', 1)
            ->assertJsonPath('data.metrics.attendance_today_breakdown.not_recorded', 2)
            ->assertJsonPath('data.metrics.pending_leave_requests', 2)
            ->assertJsonPath('data.metrics.pending_supervisor_approvals', 1)
            ->assertJsonPath('data.metrics.pending_hr_approvals', 1)
            ->assertJsonPath('data.metrics.payroll_generated_this_month', 1)
            ->assertJsonPath('data.metrics.payroll_readiness.generated_count', 1)
            ->assertJsonPath('data.metrics.payroll_readiness.missing_count', 2)
            ->assertJsonPath('data.metrics.payroll_readiness.is_ready', false)
            ->assertJsonPath('data.metrics.contracts_expiring_30_days', 1)
            ->assertJsonPath('data.metrics.contracts_expiring_60_days', 2)
            ->assertJsonPath('data.metrics.contract_expiry.contracts_expiring_30_days', 1)
            ->assertJsonCount(2, 'data.metrics.contract_expiry.preview');
    }

    public function test_employee_dashboard_returns_personal_metrics(): void
    {
        Carbon::setTestNow('2026-05-05 10:00:00');
        $employee = $this->employeeWithUser();
        $directReport = $this->employeeWithUser();
        $directReport->update(['supervisor_id' => $employee->id]);
        $inactiveDirectReport = $this->employeeWithUser();
        $inactiveDirectReport->update([
            'employment_status' => Employee::STATUS_INACTIVE,
            'supervisor_id' => $employee->id,
        ]);
        $leaveType = LeaveType::factory()->create();

        Attendance::factory()->for($employee)->create([
            'attendance_date' => '2026-05-05',
            'status' => Attendance::STATUS_LATE,
            'time_in' => '09:15:00',
        ]);
        LeaveBalance::factory()->for($employee)->for($leaveType)->create([
            'year' => 2026,
            'entitlement_days' => 12,
            'used_days' => 4,
        ]);
        LeaveRequest::factory()->for($employee)->for($leaveType)->create([
            'status' => LeaveRequest::STATUS_APPROVED,
            'created_at' => '2026-05-04 12:00:00',
        ]);
        LeaveRequest::factory()->for($directReport)->for($leaveType)->create([
            'status' => LeaveRequest::STATUS_PENDING,
            'supervisor_status' => LeaveRequest::DECISION_PENDING,
        ]);
        LeaveRequest::factory()->for($inactiveDirectReport)->for($leaveType)->create([
            'status' => LeaveRequest::STATUS_PENDING,
            'supervisor_status' => LeaveRequest::DECISION_PENDING,
        ]);
        Payroll::factory()->for($employee)->create([
            'period_year' => 2026,
            'period_month' => 4,
            'net_salary' => 10_000_000,
        ]);

        Sanctum::actingAs($employee->user);

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.role', User::ROLE_EMPLOYEE)
            ->assertJsonPath('data.metrics.has_employee_profile', true)
            ->assertJsonPath('data.metrics.attendance_today.status', Attendance::STATUS_LATE)
            ->assertJsonPath('data.metrics.remaining_leave_balance', 8)
            ->assertJsonPath('data.metrics.latest_leave_request.status', LeaveRequest::STATUS_APPROVED)
            ->assertJsonPath('data.metrics.latest_payslip.net_salary', '10000000.00')
            ->assertJsonPath('data.metrics.pending_supervisor_approvals', 1);
    }

    public function test_employee_dashboard_handles_missing_employee_profile(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_EMPLOYEE]));

        $this->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('data.metrics.has_employee_profile', false)
            ->assertJsonPath('data.metrics.attendance_today', null)
            ->assertJsonPath('data.metrics.remaining_leave_balance', 0);
    }

    private function employeeWithUser(): Employee
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();

        return Employee::factory()
            ->for($user)
            ->for($department)
            ->for($position)
            ->create(['employment_status' => Employee::STATUS_ACTIVE]);
    }
}
