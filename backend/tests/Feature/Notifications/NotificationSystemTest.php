<?php

namespace Tests\Feature\Notifications;

use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Notification;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_hr_gets_operational_reminders(): void
    {
        Carbon::setTestNow('2026-05-06 09:00:00');

        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $contractEmployee = Employee::factory()->create([
            'company_id' => $hr->company_id,
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWT,
            'contract_end_date' => '2026-05-26',
        ]);
        Employee::factory()->create([
            'company_id' => $hr->company_id,
            'employment_type' => Employee::EMPLOYMENT_TYPE_PROBATION,
            'contract_end_date' => '2026-05-16',
        ]);
        $leaveType = LeaveType::factory()->create(['company_id' => $hr->company_id]);
        LeaveRequest::factory()
            ->for($contractEmployee)
            ->for($leaveType)
            ->create([
                'company_id' => $hr->company_id,
                'status' => LeaveRequest::STATUS_PENDING,
                'supervisor_status' => LeaveRequest::DECISION_APPROVED,
                'hr_status' => LeaveRequest::DECISION_PENDING,
            ]);

        Sanctum::actingAs($hr);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 4);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(4, 'data.notifications')
            ->assertJsonFragment(['type' => Notification::TYPE_CONTRACT_EXPIRY])
            ->assertJsonFragment(['type' => Notification::TYPE_PROBATION_ENDING])
            ->assertJsonFragment(['type' => Notification::TYPE_PENDING_APPROVAL])
            ->assertJsonFragment(['type' => Notification::TYPE_PAYROLL_ALERT]);
    }

    public function test_supervisor_gets_team_leave_approval_reminder(): void
    {
        $supervisorUser = User::factory()->create(['role' => User::ROLE_EMPLOYEE]);
        $supervisor = Employee::factory()
            ->for($supervisorUser)
            ->create(['company_id' => $supervisorUser->company_id]);
        $employee = Employee::factory()->create([
            'company_id' => $supervisorUser->company_id,
            'supervisor_id' => $supervisor->id,
        ]);
        $leaveType = LeaveType::factory()->create(['company_id' => $supervisorUser->company_id]);
        LeaveRequest::factory()
            ->for($employee)
            ->for($leaveType)
            ->create([
                'company_id' => $supervisorUser->company_id,
                'status' => LeaveRequest::STATUS_PENDING,
                'supervisor_status' => LeaveRequest::DECISION_PENDING,
                'hr_status' => LeaveRequest::DECISION_PENDING,
            ]);

        Sanctum::actingAs($supervisorUser);

        $this->getJson('/api/notifications')
            ->assertOk()
            ->assertJsonCount(1, 'data.notifications')
            ->assertJsonPath('data.notifications.0.title', 'Team leave pending')
            ->assertJsonPath('data.notifications.0.action_url', '/leaves/approvals');
    }

    public function test_user_can_mark_notifications_read(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_HR]);
        $notification = Notification::query()->create([
            'company_id' => $user->company_id,
            'user_id' => $user->id,
            'type' => Notification::TYPE_PAYROLL_ALERT,
            'severity' => Notification::SEVERITY_WARNING,
            'title' => 'Payroll pending',
            'message' => 'Payroll is not generated.',
            'reminder_key' => 'payroll-missing:2026-05',
            'triggered_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $this->postJson("/api/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('data.notification.is_read', true);

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_stale_payroll_reminder_is_marked_read_when_payroll_is_complete(): void
    {
        Carbon::setTestNow('2026-05-06 09:00:00');

        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $employee = Employee::factory()->create(['company_id' => $hr->company_id]);
        Payroll::factory()
            ->for($employee)
            ->create([
                'company_id' => $hr->company_id,
                'period_year' => 2026,
                'period_month' => 5,
            ]);
        $notification = Notification::query()->create([
            'company_id' => $hr->company_id,
            'user_id' => $hr->id,
            'type' => Notification::TYPE_PAYROLL_ALERT,
            'severity' => Notification::SEVERITY_WARNING,
            'title' => 'Payroll pending',
            'message' => 'Payroll is not generated.',
            'reminder_key' => 'payroll-missing:2026-05',
            'triggered_at' => now(),
        ]);

        Sanctum::actingAs($hr);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_stale_payroll_reminder_is_marked_read_when_no_active_employees_exist(): void
    {
        Carbon::setTestNow('2026-05-06 09:00:00');

        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $notification = Notification::query()->create([
            'company_id' => $hr->company_id,
            'user_id' => $hr->id,
            'type' => Notification::TYPE_PAYROLL_ALERT,
            'severity' => Notification::SEVERITY_WARNING,
            'title' => 'Payroll pending',
            'message' => 'Payroll is not generated.',
            'reminder_key' => 'payroll-missing:2026-05',
            'triggered_at' => now(),
        ]);

        Sanctum::actingAs($hr);

        $this->getJson('/api/notifications/unread-count')
            ->assertOk()
            ->assertJsonPath('data.unread_count', 0);

        $this->assertNotNull($notification->refresh()->read_at);
    }
}
