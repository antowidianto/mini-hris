<?php

namespace Tests\Feature\CompanySettings;

use App\Models\AuditLog;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanySettingTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_and_update_company_settings(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $this->getJson('/api/company-settings')
            ->assertOk()
            ->assertJsonPath('data.company_settings.company_name', 'Mini HRIS Indonesia')
            ->assertJsonPath('data.company_settings.late_tolerance_minutes', 10);

        $this->putJson('/api/company-settings', $this->validPayload([
            'company_name' => 'PT Nusantara Retail Mandiri',
            'company_npwp' => '01.234.567.8-999.000',
            'late_tolerance_minutes' => 15,
            'meal_allowance_default' => 25000,
        ]))
            ->assertOk()
            ->assertJsonPath('message', 'Company settings updated successfully')
            ->assertJsonPath('data.company_settings.company_name', 'PT Nusantara Retail Mandiri')
            ->assertJsonPath('data.company_settings.late_tolerance_minutes', 15)
            ->assertJsonPath('data.company_settings.meal_allowance_default', '25000.00');

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $admin->id,
            'module' => AuditLog::MODULE_SETTINGS,
            'action' => AuditLog::ACTION_UPDATED,
        ]);
    }

    public function test_company_settings_validate_working_hours(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $this->putJson('/api/company-settings', $this->validPayload([
            'default_work_start' => '17:00',
            'default_work_end' => '08:00',
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['default_work_end'], 'errors');
    }

    public function test_only_admin_can_manage_company_settings(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_HR]));

        $this->getJson('/api/company-settings')
            ->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_EMPLOYEE]));

        $this->putJson('/api/company-settings', $this->validPayload())
            ->assertForbidden();
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge(CompanySetting::defaults(), [
            'default_work_start' => '08:00',
            'default_work_end' => '17:00',
        ], $overrides);
    }
}
