<?php

namespace Tests\Feature\Payroll;

use App\Models\ApprovalFlow;
use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PayrollManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_hr_can_generate_monthly_payroll_with_attendance_and_unpaid_leave_deductions(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $employee = $this->employeeWithUser(basicSalary: 22_000_000);
        $unpaidLeave = LeaveType::factory()->create(['is_paid' => false]);
        CompanySetting::query()->create(array_merge(CompanySetting::defaults(), [
            'payroll_work_days_per_month' => 22,
            'late_deduction_amount' => 25_000,
            'bpjs_kesehatan_employee_rate' => 1,
            'bpjs_kesehatan_employer_rate' => 4,
            'bpjs_jht_employee_rate' => 2,
            'bpjs_jht_employer_rate' => 3.7,
            'bpjs_jp_employee_rate' => 1,
            'bpjs_jp_employer_rate' => 2,
        ]));

        Attendance::factory()->for($employee)->create([
            'attendance_date' => '2026-05-03',
            'status' => Attendance::STATUS_ABSENT,
            'time_in' => null,
            'time_out' => null,
        ]);
        Attendance::factory()->for($employee)->create([
            'attendance_date' => '2026-05-04',
            'status' => Attendance::STATUS_LATE,
            'time_in' => '09:30:00',
        ]);
        LeaveRequest::factory()->for($employee)->for($unpaidLeave)->create([
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-11',
            'total_days' => 2,
            'status' => LeaveRequest::STATUS_APPROVED,
        ]);

        Sanctum::actingAs($hr);
        Carbon::setTestNow('2026-05-31 18:00:00');

        $this->postJson('/api/payroll/generate', [
            'period_year' => 2026,
            'period_month' => 5,
            'employee_id' => $employee->id,
            'fixed_allowance' => 500_000,
            'other_deduction' => 100_000,
        ])
            ->assertCreated()
            ->assertJsonPath('data.payrolls.0.employee.full_name', $employee->full_name)
            ->assertJsonPath('data.payrolls.0.gross_salary', '22500000.00')
            ->assertJsonPath('data.payrolls.0.attendance_deduction', '1000000.00')
            ->assertJsonPath('data.payrolls.0.late_deduction', '25000.00')
            ->assertJsonPath('data.payrolls.0.unpaid_leave_deduction', '2000000.00')
            ->assertJsonPath('data.payrolls.0.total_employee_bpjs', '880000.00')
            ->assertJsonPath('data.payrolls.0.total_employer_bpjs', '2134000.00')
            ->assertJsonPath('data.payrolls.0.total_deductions', '4005000.00')
            ->assertJsonPath('data.payrolls.0.take_home_pay', '18495000.00')
            ->assertJsonPath('data.payrolls.0.net_salary', '18495000.00')
            ->assertJsonPath('data.payrolls.0.absent_days', 1)
            ->assertJsonPath('data.payrolls.0.late_days', 1)
            ->assertJsonPath('data.payrolls.0.unpaid_leave_days', 2);
    }

    public function test_duplicate_payroll_generation_is_prevented(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $employee = $this->employeeWithUser();

        Payroll::factory()->for($employee)->create([
            'period_year' => 2026,
            'period_month' => 5,
        ]);

        Sanctum::actingAs($hr);

        $this->postJson('/api/payroll/generate', [
            'period_year' => 2026,
            'period_month' => 5,
            'employee_id' => $employee->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['period_month'], 'errors');
    }

    public function test_admin_can_list_and_view_payroll_records(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $employee = $this->employeeWithUser();
        Payroll::factory()->for($employee)->create([
            'period_year' => 2026,
            'period_month' => 5,
            'net_salary' => 12_500_000,
        ]);

        $this->getJson('/api/payroll?period_year=2026&period_month=5')
            ->assertOk()
            ->assertJsonCount(1, 'data.payrolls')
            ->assertJsonPath('data.payrolls.0.net_salary', '12500000.00');

        $payroll = Payroll::query()->firstOrFail();

        $this->getJson("/api/payroll/{$payroll->id}")
            ->assertOk()
            ->assertJsonPath('data.payroll.employee.id', $employee->id);
    }

    public function test_legacy_payroll_records_fallback_to_existing_net_salary(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $employee = $this->employeeWithUser();
        Payroll::factory()->for($employee)->create([
            'period_year' => 2026,
            'period_month' => 5,
            'basic_salary' => 12_000_000,
            'allowance' => 500_000,
            'deduction' => 100_000,
            'gross_salary' => 0,
            'other_deduction' => 0,
            'take_home_pay' => 0,
            'net_salary' => 12_400_000,
        ]);

        $payroll = Payroll::query()->firstOrFail();

        $this->getJson("/api/payroll/{$payroll->id}")
            ->assertOk()
            ->assertJsonPath('data.payroll.gross_salary', '12500000.00')
            ->assertJsonPath('data.payroll.other_deduction', '100000.00')
            ->assertJsonPath('data.payroll.take_home_pay', '12400000.00');
    }

    public function test_employee_can_only_view_own_payslips(): void
    {
        $employee = $this->employeeWithUser();
        $otherEmployee = $this->employeeWithUser();
        $ownPayroll = Payroll::factory()->for($employee)->create([
            'period_year' => 2026,
            'period_month' => 5,
        ]);
        $otherPayroll = Payroll::factory()->for($otherEmployee)->create([
            'period_year' => 2026,
            'period_month' => 5,
        ]);

        Sanctum::actingAs($employee->user);

        $this->getJson('/api/payslips')
            ->assertOk()
            ->assertJsonCount(1, 'data.payrolls')
            ->assertJsonPath('data.payrolls.0.id', $ownPayroll->id);

        $this->getJson("/api/payslips/{$ownPayroll->id}")
            ->assertOk()
            ->assertJsonPath('data.payroll.id', $ownPayroll->id);

        $this->getJson("/api/payslips/{$otherPayroll->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payroll'], 'errors');
    }

    public function test_payroll_uses_configured_multi_step_approval_flow(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = $this->employeeWithUser();

        ApprovalFlow::query()->where('company_id', $hr->company_id)->where('module', ApprovalFlow::MODULE_PAYROLL)->delete();
        ApprovalFlow::query()->create([
            'company_id' => $hr->company_id,
            'module' => ApprovalFlow::MODULE_PAYROLL,
            'step_order' => 1,
            'role' => User::ROLE_HR,
            'is_active' => true,
        ]);
        ApprovalFlow::query()->create([
            'company_id' => $hr->company_id,
            'module' => ApprovalFlow::MODULE_PAYROLL,
            'step_order' => 2,
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);

        Sanctum::actingAs($hr);

        $payroll = $this->postJson('/api/payroll/generate', [
            'period_year' => 2026,
            'period_month' => 5,
            'employee_id' => $employee->id,
        ])
            ->assertCreated()
            ->assertJsonPath('data.payrolls.0.approval_status', Payroll::APPROVAL_PENDING)
            ->assertJsonPath('data.payrolls.0.current_approval_step.role', User::ROLE_HR)
            ->json('data.payrolls.0');

        Sanctum::actingAs($employee->user);

        $this->getJson('/api/payslips')
            ->assertOk()
            ->assertJsonCount(0, 'data.payrolls');

        Sanctum::actingAs($hr);

        $this->postJson("/api/payroll/{$payroll['id']}/approve", [
            'approval_notes' => 'HR checked payroll.',
        ])
            ->assertOk()
            ->assertJsonPath('data.payroll.approval_status', Payroll::APPROVAL_PENDING)
            ->assertJsonPath('data.payroll.current_approval_step.role', User::ROLE_ADMIN);

        Sanctum::actingAs($admin);

        $this->postJson("/api/payroll/{$payroll['id']}/approve", [
            'approval_notes' => 'Ready for release.',
        ])
            ->assertOk()
            ->assertJsonPath('data.payroll.approval_status', Payroll::APPROVAL_APPROVED)
            ->assertJsonPath('data.payroll.approver.email', $admin->email);

        Sanctum::actingAs($employee->user);

        $this->getJson('/api/payslips')
            ->assertOk()
            ->assertJsonCount(1, 'data.payrolls')
            ->assertJsonPath('data.payrolls.0.id', $payroll['id']);
    }

    public function test_roles_are_restricted_for_payroll_endpoints(): void
    {
        $employee = $this->employeeWithUser();

        Sanctum::actingAs($employee->user);

        $this->getJson('/api/payroll')
            ->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_HR]));

        $this->getJson('/api/payslips')
            ->assertForbidden();
    }

    private function employeeWithUser(int $basicSalary = 12_000_000): Employee
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();

        return Employee::factory()
            ->for($user)
            ->for($department)
            ->for($position)
            ->create([
                'employment_status' => Employee::STATUS_ACTIVE,
                'basic_salary' => $basicSalary,
            ]);
    }
}
