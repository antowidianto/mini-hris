<?php

namespace Tests\Feature\AuditLogs;

use App\Models\AuditLog;
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

class AuditLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_logout_are_logged(): void
    {
        $user = User::factory()->create([
            'email' => 'audit-login@example.com',
            'password' => 'password',
        ]);

        $login = $this->postJson('/api/auth/login', [
            'email' => 'audit-login@example.com',
            'password' => 'password',
        ])->assertOk();

        $token = $login->json('data.token');

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => AuditLog::ACTION_LOGIN,
            'module' => AuditLog::MODULE_AUTH,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => AuditLog::ACTION_LOGOUT,
            'module' => AuditLog::MODULE_AUTH,
        ]);
    }

    public function test_admin_can_list_and_filter_audit_logs(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();

        $this->postJson('/api/employees', [
            'employee_id' => 'EMP-AUDIT-1',
            'full_name' => 'Audit Employee',
            'email' => 'audit.employee@example.com',
            'department_id' => $department->id,
            'position_id' => $position->id,
            'join_date' => '2026-01-01',
            'employment_status' => Employee::STATUS_ACTIVE,
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWTT,
            'tax_dependents' => 0,
            'contract_start_date' => null,
            'contract_end_date' => null,
            'basic_salary' => 12_000_000,
            'user_id' => null,
        ])->assertCreated();

        $this->getJson('/api/audit-logs?module='.AuditLog::MODULE_EMPLOYEE.'&action='.AuditLog::ACTION_CREATED)
            ->assertOk()
            ->assertJsonPath('data.audit_logs.0.module', AuditLog::MODULE_EMPLOYEE)
            ->assertJsonPath('data.audit_logs.0.action', AuditLog::ACTION_CREATED)
            ->assertJsonPath('data.audit_logs.0.user.email', $admin->email)
            ->assertJsonPath('data.audit_logs.0.module_label', 'Employees')
            ->assertJsonPath('data.audit_logs.0.action_label', 'Created')
            ->assertJsonPath('data.filters.modules.0.value', AuditLog::MODULE_AUTH);
    }

    public function test_admin_can_filter_audit_logs_by_company_user_and_search(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $hr = User::factory()->create([
            'company_id' => $admin->company_id,
            'role' => User::ROLE_HR,
            'name' => 'Audit HR',
            'email' => 'audit-hr@example.com',
        ]);
        $otherCompanyUser = User::factory()->for(Company::factory())->create(['role' => User::ROLE_ADMIN]);

        AuditLog::factory()->for($admin->company)->for($hr, 'user')->create([
            'action' => AuditLog::ACTION_UPDATED,
            'module' => AuditLog::MODULE_SETTINGS,
            'description' => 'Updated approval flow configuration.',
            'created_at' => '2026-05-08 10:00:00',
        ]);
        AuditLog::factory()->for($otherCompanyUser->company)->for($otherCompanyUser, 'user')->create([
            'action' => AuditLog::ACTION_DELETED,
            'module' => AuditLog::MODULE_DOCUMENT,
            'description' => 'Deleted outside company document.',
            'created_at' => '2026-05-08 11:00:00',
        ]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/audit-logs?user_id='.$hr->id.'&search=approval&date_from=2026-05-08&date_to=2026-05-08')
            ->assertOk()
            ->assertJsonCount(1, 'data.audit_logs')
            ->assertJsonPath('data.audit_logs.0.user.email', $hr->email)
            ->assertJsonPath('data.audit_logs.0.summary', 'Audit HR Updated Settings')
            ->assertJsonPath('data.filters.users.0.email', $hr->email);

        $this->getJson('/api/audit-logs?user_id='.$otherCompanyUser->id)
            ->assertUnprocessable();
    }

    public function test_employee_leave_and_payroll_events_are_logged(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $employee = $this->employeeWithUser();
        $leaveType = LeaveType::factory()->create();
        $approvedLeave = LeaveRequest::factory()->for($employee)->for($leaveType)->create();
        $rejectedLeave = LeaveRequest::factory()->for($employee)->for($leaveType)->create([
            'start_date' => '2026-06-10',
            'end_date' => '2026-06-11',
            'total_days' => 2,
        ]);

        Sanctum::actingAs($admin);

        $this->postJson("/api/leaves/{$approvedLeave->id}/approve")
            ->assertOk();
        $this->postJson("/api/leaves/{$rejectedLeave->id}/reject")
            ->assertOk();
        $this->postJson('/api/payroll/generate', [
            'period_year' => 2026,
            'period_month' => 5,
            'employee_id' => $employee->id,
        ])->assertCreated();

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_APPROVED,
            'module' => AuditLog::MODULE_LEAVE,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_REJECTED,
            'module' => AuditLog::MODULE_LEAVE,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'action' => AuditLog::ACTION_GENERATED,
            'module' => AuditLog::MODULE_PAYROLL,
        ]);
    }

    public function test_only_admin_can_view_audit_logs(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_HR]));

        $this->getJson('/api/audit-logs')
            ->assertForbidden();
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
