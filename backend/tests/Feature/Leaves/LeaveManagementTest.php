<?php

namespace Tests\Feature\Leaves;

use App\Models\ApprovalFlow;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeaveManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_employee_can_view_balances_and_submit_leave_request(): void
    {
        [$user, $leaveType] = $this->employeeWithLeaveBalance(entitlement: 12);
        Sanctum::actingAs($user);
        Carbon::setTestNow('2026-05-05 08:00:00');

        $this->getJson('/api/leaves/balances')
            ->assertOk()
            ->assertJsonPath('data.leave_balances.0.entitlement_days', 12)
            ->assertJsonPath('data.leave_balances.0.remaining_days', 12);

        $this->postJson('/api/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-11',
            'end_date' => '2026-05-13',
            'reason' => 'Family event',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Leave request submitted successfully')
            ->assertJsonPath('data.leave_request.total_days', 3)
            ->assertJsonPath('data.leave_request.status', LeaveRequest::STATUS_PENDING)
            ->assertJsonPath('data.leave_request.supervisor_status', LeaveRequest::DECISION_APPROVED)
            ->assertJsonPath('data.leave_request.hr_status', LeaveRequest::DECISION_PENDING);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $user->employee->id,
            'leave_type_id' => $leaveType->id,
            'used_days' => 0,
        ]);
    }

    public function test_employee_cannot_submit_leave_with_insufficient_balance_or_overlap(): void
    {
        [$user, $leaveType] = $this->employeeWithLeaveBalance(entitlement: 3);
        Sanctum::actingAs($user);
        Carbon::setTestNow('2026-05-05 08:00:00');

        LeaveRequest::factory()->for($user->employee)->for($leaveType)->create([
            'start_date' => '2026-05-11',
            'end_date' => '2026-05-12',
            'total_days' => 2,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        $this->postJson('/api/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-15',
            'end_date' => '2026-05-16',
            'reason' => 'Personal matter',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['leave_type_id'], 'errors');

        $this->postJson('/api/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-12',
            'end_date' => '2026-05-13',
            'reason' => 'Personal matter',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date'], 'errors');
    }

    public function test_hr_can_approve_leave_and_deduct_balance(): void
    {
        [$user, $leaveType] = $this->employeeWithLeaveBalance(entitlement: 12);
        $approver = User::factory()->create(['role' => User::ROLE_HR]);
        $leaveRequest = LeaveRequest::factory()->for($user->employee)->for($leaveType)->create([
            'start_date' => '2026-05-11',
            'end_date' => '2026-05-13',
            'total_days' => 3,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        Sanctum::actingAs($approver);
        Carbon::setTestNow('2026-05-06 10:00:00');

        $this->postJson("/api/leaves/{$leaveRequest->id}/approve", [
            'approval_notes' => 'Approved for planned leave.',
        ])
            ->assertOk()
            ->assertJsonPath('data.leave_request.status', LeaveRequest::STATUS_APPROVED)
            ->assertJsonPath('data.leave_request.hr_status', LeaveRequest::DECISION_APPROVED)
            ->assertJsonPath('data.leave_request.approver.email', $approver->email);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $user->employee->id,
            'leave_type_id' => $leaveType->id,
            'used_days' => 3,
        ]);

        $this->postJson("/api/leaves/{$leaveRequest->id}/approve")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status'], 'errors');
    }

    public function test_hr_can_reject_leave_without_deducting_balance(): void
    {
        [$user, $leaveType] = $this->employeeWithLeaveBalance(entitlement: 12);
        $approver = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $leaveRequest = LeaveRequest::factory()->for($user->employee)->for($leaveType)->create([
            'total_days' => 2,
            'status' => LeaveRequest::STATUS_PENDING,
        ]);

        Sanctum::actingAs($approver);

        $this->postJson("/api/leaves/{$leaveRequest->id}/reject", [
            'approval_notes' => 'Business coverage required.',
        ])
            ->assertOk()
            ->assertJsonPath('data.leave_request.status', LeaveRequest::STATUS_REJECTED)
            ->assertJsonPath('data.leave_request.hr_status', LeaveRequest::DECISION_REJECTED);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $user->employee->id,
            'leave_type_id' => $leaveType->id,
            'used_days' => 0,
        ]);
    }

    public function test_roles_are_restricted_for_leave_endpoints(): void
    {
        [$user] = $this->employeeWithLeaveBalance();
        $leaveRequest = LeaveRequest::factory()->for($user->employee)->create();

        Sanctum::actingAs($user);

        $this->getJson('/api/leaves/approvals')
            ->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_HR]));

        $this->postJson('/api/leaves', [
            'leave_type_id' => $leaveRequest->leave_type_id,
            'start_date' => '2026-05-11',
            'end_date' => '2026-05-11',
            'reason' => 'Personal matter',
        ])
            ->assertForbidden();
    }

    public function test_supervisor_can_approve_leave_before_hr_final_approval(): void
    {
        [$employeeUser, $leaveType, $supervisorUser] = $this->employeeWithSupervisorAndLeaveBalance();
        $hr = User::factory()->create(['role' => User::ROLE_HR]);

        Sanctum::actingAs($employeeUser);
        Carbon::setTestNow('2026-05-05 08:00:00');

        $request = $this->postJson('/api/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-11',
            'end_date' => '2026-05-12',
            'reason' => 'Family event',
        ])
            ->assertCreated()
            ->assertJsonPath('data.leave_request.supervisor_status', LeaveRequest::DECISION_PENDING)
            ->json('data.leave_request');

        Sanctum::actingAs($supervisorUser);

        $this->getJson('/api/leaves/supervisor-approvals')
            ->assertOk()
            ->assertJsonCount(1, 'data.leave_requests')
            ->assertJsonPath('data.leave_requests.0.id', $request['id']);

        $this->postJson("/api/leaves/{$request['id']}/supervisor-approve", [
            'approval_notes' => 'Coverage is arranged.',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Leave request approved by supervisor successfully')
            ->assertJsonPath('data.leave_request.status', LeaveRequest::STATUS_PENDING)
            ->assertJsonPath('data.leave_request.supervisor_status', LeaveRequest::DECISION_APPROVED)
            ->assertJsonPath('data.leave_request.hr_status', LeaveRequest::DECISION_PENDING)
            ->assertJsonPath('data.leave_request.supervisor_approver.email', $supervisorUser->email);

        Sanctum::actingAs($hr);

        $this->getJson('/api/leaves/approvals')
            ->assertOk()
            ->assertJsonCount(1, 'data.leave_requests')
            ->assertJsonPath('data.leave_requests.0.id', $request['id']);

        $this->postJson("/api/leaves/{$request['id']}/approve", [
            'approval_notes' => 'Final HR approval.',
        ])
            ->assertOk()
            ->assertJsonPath('data.leave_request.status', LeaveRequest::STATUS_APPROVED)
            ->assertJsonPath('data.leave_request.hr_status', LeaveRequest::DECISION_APPROVED)
            ->assertJsonPath('data.leave_request.hr_approver.email', $hr->email);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employeeUser->employee->id,
            'leave_type_id' => $leaveType->id,
            'used_days' => 2,
        ]);
    }

    public function test_hr_cannot_approve_before_supervisor_approval(): void
    {
        [$employeeUser, $leaveType] = $this->employeeWithSupervisorAndLeaveBalance();
        $hr = User::factory()->create(['role' => User::ROLE_HR]);

        $leaveRequest = LeaveRequest::factory()->for($employeeUser->employee)->for($leaveType)->create([
            'status' => LeaveRequest::STATUS_PENDING,
            'supervisor_status' => LeaveRequest::DECISION_PENDING,
            'hr_status' => LeaveRequest::DECISION_PENDING,
        ]);

        Sanctum::actingAs($hr);

        $this->postJson("/api/leaves/{$leaveRequest->id}/approve")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['supervisor_status'], 'errors');
    }

    public function test_legacy_leave_requests_without_runtime_steps_remain_in_approval_queues(): void
    {
        [$employeeUser, $leaveType, $supervisorUser] = $this->employeeWithSupervisorAndLeaveBalance();
        $hr = User::factory()->create(['role' => User::ROLE_HR]);

        $supervisorPending = LeaveRequest::factory()->for($employeeUser->employee)->for($leaveType)->create([
            'status' => LeaveRequest::STATUS_PENDING,
            'supervisor_status' => LeaveRequest::DECISION_PENDING,
            'hr_status' => LeaveRequest::DECISION_PENDING,
        ]);
        $hrPending = LeaveRequest::factory()->for($employeeUser->employee)->for($leaveType)->create([
            'status' => LeaveRequest::STATUS_PENDING,
            'supervisor_status' => LeaveRequest::DECISION_APPROVED,
            'hr_status' => LeaveRequest::DECISION_PENDING,
        ]);

        Sanctum::actingAs($supervisorUser);

        $this->getJson('/api/leaves/supervisor-approvals')
            ->assertOk()
            ->assertJsonFragment(['id' => $supervisorPending->id]);

        Sanctum::actingAs($hr);

        $this->getJson('/api/leaves/approvals')
            ->assertOk()
            ->assertJsonFragment(['id' => $hrPending->id]);
    }

    public function test_leave_can_use_one_step_hr_approval_flow(): void
    {
        [$employeeUser, $leaveType] = $this->employeeWithSupervisorAndLeaveBalance();
        $hr = User::factory()->create(['role' => User::ROLE_HR]);

        ApprovalFlow::query()->where('company_id', $hr->company_id)->where('module', ApprovalFlow::MODULE_LEAVE)->update(['is_active' => false]);
        ApprovalFlow::query()->updateOrCreate(
            [
                'company_id' => $hr->company_id,
                'module' => ApprovalFlow::MODULE_LEAVE,
                'step_order' => 1,
                'role' => User::ROLE_HR,
            ],
            ['is_active' => true]
        );

        Sanctum::actingAs($employeeUser);

        $request = $this->postJson('/api/leaves', [
            'leave_type_id' => $leaveType->id,
            'start_date' => '2026-05-11',
            'end_date' => '2026-05-12',
            'reason' => 'Family event',
        ])
            ->assertCreated()
            ->assertJsonPath('data.leave_request.supervisor_status', LeaveRequest::DECISION_APPROVED)
            ->assertJsonPath('data.leave_request.current_approval_step.role', User::ROLE_HR)
            ->json('data.leave_request');

        Sanctum::actingAs($hr);

        $this->postJson("/api/leaves/{$request['id']}/approve", [
            'approval_notes' => 'One-step approval.',
        ])
            ->assertOk()
            ->assertJsonPath('data.leave_request.status', LeaveRequest::STATUS_APPROVED)
            ->assertJsonPath('data.leave_request.hr_status', LeaveRequest::DECISION_APPROVED);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employeeUser->employee->id,
            'leave_type_id' => $leaveType->id,
            'used_days' => 2,
        ]);
    }

    public function test_direct_supervisor_can_reject_leave_without_deducting_balance(): void
    {
        [$employeeUser, $leaveType, $supervisorUser] = $this->employeeWithSupervisorAndLeaveBalance();
        $otherSupervisor = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $leaveRequest = LeaveRequest::factory()->for($employeeUser->employee)->for($leaveType)->create([
            'status' => LeaveRequest::STATUS_PENDING,
            'supervisor_status' => LeaveRequest::DECISION_PENDING,
            'hr_status' => LeaveRequest::DECISION_PENDING,
            'total_days' => 2,
        ]);

        Sanctum::actingAs($otherSupervisor);

        $this->postJson("/api/leaves/{$leaveRequest->id}/supervisor-reject")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['supervisor'], 'errors');

        Sanctum::actingAs($supervisorUser);

        $this->postJson("/api/leaves/{$leaveRequest->id}/supervisor-reject", [
            'approval_notes' => 'Peak operation day.',
        ])
            ->assertOk()
            ->assertJsonPath('data.leave_request.status', LeaveRequest::STATUS_REJECTED)
            ->assertJsonPath('data.leave_request.supervisor_status', LeaveRequest::DECISION_REJECTED)
            ->assertJsonPath('data.leave_request.hr_status', LeaveRequest::DECISION_REJECTED);

        $this->assertDatabaseHas('leave_balances', [
            'employee_id' => $employeeUser->employee->id,
            'leave_type_id' => $leaveType->id,
            'used_days' => 0,
        ]);
    }

    /**
     * @return array{0: User, 1: LeaveType}
     */
    private function employeeWithLeaveBalance(int $entitlement = 12): array
    {
        $user = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();
        $employee = Employee::factory()
            ->for($user)
            ->for($department)
            ->for($position)
            ->create(['employment_status' => Employee::STATUS_ACTIVE]);
        $leaveType = LeaveType::factory()->create(['annual_entitlement' => $entitlement]);

        LeaveBalance::factory()->for($employee)->for($leaveType)->create([
            'year' => 2026,
            'entitlement_days' => $entitlement,
            'used_days' => 0,
        ]);

        return [$user->refresh()->load('employee'), $leaveType];
    }

    /**
     * @return array{0: User, 1: LeaveType, 2: User}
     */
    private function employeeWithSupervisorAndLeaveBalance(int $entitlement = 12): array
    {
        $supervisorUser = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $employeeUser = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();
        $supervisor = Employee::factory()
            ->for($supervisorUser)
            ->for($department)
            ->for($position)
            ->create(['employment_status' => Employee::STATUS_ACTIVE]);
        $employee = Employee::factory()
            ->for($employeeUser)
            ->for($department)
            ->for($position)
            ->create([
                'employment_status' => Employee::STATUS_ACTIVE,
                'supervisor_id' => $supervisor->id,
            ]);
        $leaveType = LeaveType::factory()->create(['annual_entitlement' => $entitlement]);

        LeaveBalance::factory()->for($employee)->for($leaveType)->create([
            'year' => 2026,
            'entitlement_days' => $entitlement,
            'used_days' => 0,
        ]);

        return [
            $employeeUser->refresh()->load('employee'),
            $leaveType,
            $supervisorUser->refresh()->load('employee'),
        ];
    }
}
