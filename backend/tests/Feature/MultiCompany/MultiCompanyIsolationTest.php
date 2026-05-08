<?php

namespace Tests\Feature\MultiCompany;

use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MultiCompanyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_only_sees_own_company_organization_and_employees(): void
    {
        [$companyA, $companyB] = [Company::factory()->create(['code' => 'A']), Company::factory()->create(['code' => 'B'])];
        $admin = User::factory()->for($companyA)->create(['role' => User::ROLE_ADMIN]);
        Sanctum::actingAs($admin);

        $employeeA = $this->employeeForCompany($companyA, 'EMP-A001', 'Alya Company A');
        $employeeB = $this->employeeForCompany($companyB, 'EMP-B001', 'Bima Company B');

        $this->getJson('/api/employees')
            ->assertOk()
            ->assertJsonFragment(['employee_id' => $employeeA->employee_id])
            ->assertJsonMissing(['employee_id' => $employeeB->employee_id]);

        $this->getJson('/api/employees/'.$employeeB->id)
            ->assertNotFound();

        $this->getJson('/api/departments')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Operations A'])
            ->assertJsonMissing(['name' => 'Operations B']);

        $this->getJson('/api/branches')
            ->assertOk()
            ->assertJsonFragment(['code' => 'A-HO'])
            ->assertJsonMissing(['code' => 'B-HO']);
    }

    public function test_company_settings_are_scoped_per_company(): void
    {
        [$companyA, $companyB] = [Company::factory()->create(['code' => 'A']), Company::factory()->create(['code' => 'B'])];
        $adminA = User::factory()->for($companyA)->create(['role' => User::ROLE_ADMIN]);
        $adminB = User::factory()->for($companyB)->create(['role' => User::ROLE_ADMIN]);

        Sanctum::actingAs($adminA);
        $this->putJson('/api/settings', [
            'settings' => [
                'late_tolerance_minutes' => 25,
            ],
        ])->assertOk();

        Sanctum::actingAs($adminB);
        $this->getJson('/api/settings')
            ->assertOk()
            ->assertJsonFragment([
                'key' => 'late_tolerance_minutes',
                'value' => 10,
            ]);

        $this->assertDatabaseHas('company_settings', [
            'company_id' => $companyA->id,
            'late_tolerance_minutes' => 25,
        ]);
        $this->assertDatabaseHas('company_settings', [
            'company_id' => $companyB->id,
            'late_tolerance_minutes' => 10,
        ]);
    }

    public function test_employee_numbers_are_unique_only_inside_the_same_company(): void
    {
        [$companyA, $companyB] = [Company::factory()->create(['code' => 'A']), Company::factory()->create(['code' => 'B'])];
        $adminB = User::factory()->for($companyB)->create(['role' => User::ROLE_ADMIN]);
        $this->employeeForCompany($companyA, 'EMP-SAME', 'Company A Existing');
        $target = $this->employeeForCompany($companyB, 'EMP-OTHER', 'Company B Target');
        Sanctum::actingAs($adminB);

        $this->putJson('/api/employees/'.$target->id, [
            ...$this->employeePayload($target),
            'employee_id' => 'EMP-SAME',
            'email' => 'company-b-same@example.test',
        ])
            ->assertOk()
            ->assertJsonPath('data.employee.employee_id', 'EMP-SAME');

        $otherCompanyUser = User::factory()->for($companyA)->create(['role' => User::ROLE_EMPLOYEE]);

        $this->putJson('/api/employees/'.$target->id, [
            ...$this->employeePayload($target->refresh()),
            'user_id' => $otherCompanyUser->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user_id'], 'errors');
    }

    private function employeeForCompany(Company $company, string $employeeId, string $name): Employee
    {
        $branch = Branch::factory()->for($company)->create([
            'name' => str_ends_with($employeeId, 'A001') ? 'Head Office A' : 'Head Office B',
            'code' => str_ends_with($employeeId, 'A001') ? 'A-HO' : 'B-HO',
        ]);
        $department = Department::factory()->for($company)->create([
            'name' => str_ends_with($employeeId, 'A001') ? 'Operations A' : 'Operations B',
        ]);
        $position = Position::factory()->for($company)->for($department)->create([
            'name' => str_ends_with($employeeId, 'A001') ? 'Staff A' : 'Staff B',
        ]);

        return Employee::factory()
            ->for($company)
            ->for($branch)
            ->for($department)
            ->for($position)
            ->create([
                'employee_id' => $employeeId,
                'full_name' => $name,
                'email' => strtolower($employeeId).'@example.test',
            ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function employeePayload(Employee $employee): array
    {
        return [
            'employee_id' => $employee->employee_id,
            'full_name' => $employee->full_name,
            'email' => $employee->email,
            'nik_ktp' => $employee->nik_ktp,
            'npwp' => $employee->npwp,
            'bpjs_kesehatan_number' => $employee->bpjs_kesehatan_number,
            'bpjs_ketenagakerjaan_number' => $employee->bpjs_ketenagakerjaan_number,
            'tax_marital_status' => $employee->tax_marital_status,
            'tax_dependents' => $employee->tax_dependents,
            'bank_name' => $employee->bank_name,
            'bank_account_number' => $employee->bank_account_number,
            'bank_account_holder_name' => $employee->bank_account_holder_name,
            'branch_id' => $employee->branch_id,
            'department_id' => $employee->department_id,
            'position_id' => $employee->position_id,
            'supervisor_id' => null,
            'join_date' => $employee->join_date->format('Y-m-d'),
            'employment_status' => $employee->employment_status,
            'employment_type' => $employee->employment_type,
            'contract_start_date' => $employee->contract_start_date?->format('Y-m-d'),
            'contract_end_date' => $employee->contract_end_date?->format('Y-m-d'),
            'basic_salary' => $employee->basic_salary,
            'user_id' => $employee->user_id,
        ];
    }
}
