<?php

namespace Tests\Feature\Contracts;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ContractMonitoringTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_hr_can_view_expiring_contracts(): void
    {
        Carbon::setTestNow('2026-05-05 10:00:00');
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_HR]));

        Employee::factory()->create([
            'employee_id' => 'EMP-CON-30',
            'full_name' => 'Thirty Day Contract',
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWT,
            'contract_start_date' => '2025-06-01',
            'contract_end_date' => '2026-05-25',
            'employment_status' => Employee::STATUS_ACTIVE,
        ]);
        Employee::factory()->create([
            'employee_id' => 'EMP-CON-60',
            'full_name' => 'Sixty Day Contract',
            'employment_type' => Employee::EMPLOYMENT_TYPE_PROBATION,
            'contract_start_date' => '2026-04-01',
            'contract_end_date' => '2026-06-20',
            'employment_status' => Employee::STATUS_ACTIVE,
        ]);
        Employee::factory()->create([
            'employee_id' => 'EMP-CON-PERM',
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWTT,
            'contract_end_date' => null,
            'employment_status' => Employee::STATUS_ACTIVE,
        ]);

        $this->getJson('/api/contracts/expiring?days=30')
            ->assertOk()
            ->assertJsonCount(1, 'data.contracts')
            ->assertJsonPath('data.contracts.0.employee_id', 'EMP-CON-30')
            ->assertJsonPath('data.contracts.0.days_remaining', 20);

        $this->getJson('/api/contracts/expiring?days=60')
            ->assertOk()
            ->assertJsonCount(2, 'data.contracts');
    }

    public function test_hr_can_view_history_and_record_contract_renewal(): void
    {
        Carbon::setTestNow('2026-05-05 10:00:00');
        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        Sanctum::actingAs($hr);

        $employee = Employee::factory()->create([
            'employee_id' => 'EMP-REN-01',
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWT,
            'contract_start_date' => '2025-06-01',
            'contract_end_date' => '2026-05-31',
        ]);
        EmployeeContract::factory()->for($employee)->create([
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWT,
            'contract_start_date' => '2025-06-01',
            'contract_end_date' => '2026-05-31',
            'renewal_date' => '2025-06-01',
            'document_path' => 'contracts/EMP-REN-01-old.pdf',
        ]);

        $this->getJson("/api/employees/{$employee->id}/contracts")
            ->assertOk()
            ->assertJsonCount(1, 'data.contracts')
            ->assertJsonPath('data.contracts.0.document_path', 'contracts/EMP-REN-01-old.pdf');

        $this->postJson("/api/employees/{$employee->id}/contracts", [
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWT,
            'contract_start_date' => '2026-06-01',
            'contract_end_date' => '2027-05-31',
            'renewal_date' => '2026-05-05',
            'document_path' => 'contracts/EMP-REN-01-2026.pdf',
            'notes' => 'Renewed after HR review.',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'Contract renewal recorded successfully')
            ->assertJsonPath('data.contract.document_path', 'contracts/EMP-REN-01-2026.pdf');

        $this->assertDatabaseHas('employees', [
            'id' => $employee->id,
            'contract_start_date' => '2026-06-01',
            'contract_end_date' => '2027-05-31',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $hr->id,
            'module' => AuditLog::MODULE_CONTRACT,
            'action' => AuditLog::ACTION_CREATED,
        ]);
    }

    public function test_contract_renewal_is_validated(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $employee = Employee::factory()->create();

        $this->postJson("/api/employees/{$employee->id}/contracts", [
            'employment_type' => 'freelance',
            'contract_start_date' => '2026-06-01',
            'contract_end_date' => '2026-05-31',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['employment_type', 'contract_end_date'], 'errors');

        $this->postJson("/api/employees/{$employee->id}/contracts", [
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWT,
            'contract_start_date' => '2026-06-01',
            'contract_end_date' => null,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['contract_end_date'], 'errors');
    }

    public function test_employee_role_cannot_manage_contract_monitoring(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_EMPLOYEE]));

        $this->getJson('/api/contracts/expiring')
            ->assertForbidden();
    }
}
