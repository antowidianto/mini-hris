<?php

namespace Tests\Feature\Settings;

use App\Models\ApprovalFlow;
use App\Models\CompanySetting;
use App\Models\PayrollComponent;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConfigurationEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_manage_global_settings_registry(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonPath('data.settings.0.scope', Setting::SCOPE_COMPANY);

        $this->putJson('/api/settings', [
            'settings' => [
                'late_tolerance_minutes' => 20,
                'annual_leave_quota' => 14,
                'pph21_placeholder_mode' => 'manual',
            ],
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Settings updated successfully')
            ->assertJsonFragment([
                'key' => 'late_tolerance_minutes',
                'value' => 20,
            ]);

        $this->assertDatabaseHas('settings', [
            'key' => 'pph21_placeholder_mode',
            'scope' => Setting::SCOPE_COMPANY,
            'scope_id' => $admin->company_id,
        ]);
        $this->assertDatabaseHas('company_settings', [
            'company_id' => $admin->company_id,
            'late_tolerance_minutes' => 20,
            'annual_leave_quota' => 14,
        ]);
    }

    public function test_admin_can_configure_payroll_components(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $component = PayrollComponent::query()->create([
            'company_id' => $admin->company_id,
            'code' => 'CUSTOM_BONUS',
            'name' => 'Custom Bonus',
            'type' => PayrollComponent::TYPE_EARNING,
            'is_active' => true,
            'sort_order' => 50,
        ]);

        $this->putJson('/api/payroll-components/'.$component->id, [
            'name' => 'Monthly Bonus',
            'type' => PayrollComponent::TYPE_EARNING,
            'is_active' => false,
            'sort_order' => 12,
        ])
            ->assertOk()
            ->assertJsonPath('data.payroll_component.name', 'Monthly Bonus')
            ->assertJsonPath('data.payroll_component.is_active', false)
            ->assertJsonPath('data.payroll_component.sort_order', 12);

        $this->assertDatabaseHas('payroll_components', [
            'code' => 'CUSTOM_BONUS',
            'name' => 'Monthly Bonus',
            'is_active' => false,
        ]);
    }

    public function test_default_payroll_components_are_not_overwritten_after_admin_changes(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->getJson('/api/payroll-components')->assertOk();
        $component = PayrollComponent::query()->where('code', 'BASIC_SALARY')->firstOrFail();

        $this->putJson('/api/payroll-components/'.$component->id, [
            'name' => 'Gaji Pokok',
            'type' => PayrollComponent::TYPE_EARNING,
            'is_active' => false,
            'sort_order' => 99,
        ])->assertOk();

        $this->getJson('/api/payroll-components')
            ->assertOk()
            ->assertJsonFragment([
                'code' => 'BASIC_SALARY',
                'name' => 'Gaji Pokok',
                'is_active' => false,
                'sort_order' => 99,
            ]);
    }

    public function test_admin_can_replace_approval_flow_configuration(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->putJson('/api/approval-flows', [
            'flows' => [
                [
                    'module' => ApprovalFlow::MODULE_LEAVE,
                    'step_order' => 1,
                    'role' => User::ROLE_HR,
                    'is_active' => true,
                ],
                [
                    'module' => ApprovalFlow::MODULE_PAYROLL,
                    'step_order' => 1,
                    'role' => User::ROLE_ADMIN,
                    'is_active' => true,
                ],
            ],
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Approval flows updated successfully')
            ->assertJsonFragment([
                'module' => ApprovalFlow::MODULE_LEAVE,
                'role' => User::ROLE_HR,
            ]);

        $this->assertDatabaseHas('approval_flows', [
            'module' => ApprovalFlow::MODULE_PAYROLL,
            'step_order' => 1,
            'role' => User::ROLE_ADMIN,
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('approval_flows', [
            'module' => ApprovalFlow::MODULE_LEAVE,
            'step_order' => 2,
            'role' => User::ROLE_HR,
            'is_active' => false,
        ]);
    }

    public function test_approval_flow_configuration_rejects_ambiguous_or_missing_runtime_steps(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->putJson('/api/approval-flows', [
            'flows' => [
                [
                    'module' => ApprovalFlow::MODULE_LEAVE,
                    'step_order' => 1,
                    'role' => User::ROLE_HR,
                    'is_active' => true,
                ],
                [
                    'module' => ApprovalFlow::MODULE_LEAVE,
                    'step_order' => 1,
                    'role' => User::ROLE_ADMIN,
                    'is_active' => true,
                ],
                [
                    'module' => ApprovalFlow::MODULE_PAYROLL,
                    'step_order' => 1,
                    'role' => User::ROLE_HR,
                    'is_active' => true,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['flows.1.step_order'], 'errors');

        $this->putJson('/api/approval-flows', [
            'flows' => [
                [
                    'module' => ApprovalFlow::MODULE_LEAVE,
                    'step_order' => 1,
                    'role' => User::ROLE_HR,
                    'is_active' => true,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['flows'], 'errors');

        $this->putJson('/api/approval-flows', [
            'flows' => [
                [
                    'module' => ApprovalFlow::MODULE_LEAVE,
                    'step_order' => 1,
                    'role' => 'supervisor',
                    'is_active' => true,
                ],
                [
                    'module' => ApprovalFlow::MODULE_PAYROLL,
                    'step_order' => 1,
                    'role' => 'supervisor',
                    'is_active' => true,
                ],
            ],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['flows', 'flows.1.role'], 'errors');
    }

    public function test_only_admin_can_manage_configuration_engine(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_HR]));

        $this->getJson('/api/settings')->assertForbidden();
        $this->getJson('/api/payroll-components')->assertForbidden();
        $this->getJson('/api/approval-flows')->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_EMPLOYEE]));

        $this->putJson('/api/settings', ['settings' => CompanySetting::defaults()])
            ->assertForbidden();
    }
}
