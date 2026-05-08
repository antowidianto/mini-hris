<?php

namespace Tests\Feature\Employees;

use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_employee(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $department = Department::factory()->create(['name' => 'Human Resources']);
        $position = Position::factory()->for($department)->create(['name' => 'HR Officer']);
        $branch = Branch::factory()->create(['name' => 'Head Office Jakarta', 'code' => 'HO-JKT']);
        $supervisor = Employee::factory()->for($department)->for($position)->for($branch)->create([
            'employee_id' => 'EMP-0999',
            'full_name' => 'HR Manager',
        ]);

        $response = $this->postJson('/api/employees', [
            'employee_id' => 'EMP-1001',
            'full_name' => 'Jane People',
            'email' => 'jane.people@example.com',
            'nik_ktp' => '3171010101011001',
            'npwp' => '02.100.100.1-001.000',
            'bpjs_kesehatan_number' => '0002000000001',
            'bpjs_ketenagakerjaan_number' => '20000000001',
            'tax_marital_status' => Employee::TAX_STATUS_MARRIED,
            'tax_dependents' => 1,
            'bank_name' => 'BCA',
            'bank_account_number' => '9876543210',
            'bank_account_holder_name' => 'Jane People',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'supervisor_id' => $supervisor->id,
            'join_date' => '2024-01-15',
            'employment_status' => Employee::STATUS_ACTIVE,
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWT,
            'contract_start_date' => '2024-01-15',
            'contract_end_date' => '2025-01-14',
            'basic_salary' => 15000000,
            'user_id' => null,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('message', 'Employee created successfully')
            ->assertJsonPath('data.employee.employee_id', 'EMP-1001')
            ->assertJsonPath('data.employee.nik_ktp', '3171010101011001')
            ->assertJsonPath('data.employee.employment_type', Employee::EMPLOYMENT_TYPE_PKWT)
            ->assertJsonPath('data.employee.branch.name', 'Head Office Jakarta')
            ->assertJsonPath('data.employee.department.name', 'Human Resources')
            ->assertJsonPath('data.employee.position.name', 'HR Officer')
            ->assertJsonPath('data.employee.supervisor.full_name', 'HR Manager');

        $this->assertDatabaseHas('employees', [
            'employee_id' => 'EMP-1001',
            'nik_ktp' => '3171010101011001',
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWT,
            'branch_id' => $branch->id,
            'supervisor_id' => $supervisor->id,
            'employment_status' => Employee::STATUS_ACTIVE,
        ]);
    }

    public function test_hr_can_search_filter_and_paginate_employees(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_HR]));

        $hr = Department::factory()->create(['name' => 'Human Resources']);
        $finance = Department::factory()->create(['name' => 'Finance']);
        $hrPosition = Position::factory()->for($hr)->create(['name' => 'HR Officer']);
        $financePosition = Position::factory()->for($finance)->create(['name' => 'Finance Officer']);
        $jakarta = Branch::factory()->create(['name' => 'Jakarta Branch', 'code' => 'JKT']);
        $bandung = Branch::factory()->create(['name' => 'Bandung Outlet', 'code' => 'BDG']);

        Employee::factory()->for($hr)->for($hrPosition)->for($jakarta)->create([
            'employee_id' => 'EMP-2001',
            'full_name' => 'Alya Searchable',
            'email' => 'alya@example.com',
            'nik_ktp' => '3171010101012001',
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWT,
        ]);
        Employee::factory()->for($finance)->for($financePosition)->for($bandung)->create([
            'employee_id' => 'EMP-2002',
            'full_name' => 'Bima Finance',
            'email' => 'bima@example.com',
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWTT,
        ]);

        $response = $this->getJson('/api/employees?search=3171010101012001&branch_id='.$jakarta->id.'&department_id='.$hr->id.'&employment_type='.Employee::EMPLOYMENT_TYPE_PKWT.'&per_page=5');

        $response
            ->assertOk()
            ->assertJsonPath('data.employees.0.employee_id', 'EMP-2001')
            ->assertJsonPath('data.employees.0.branch.name', 'Jakarta Branch')
            ->assertJsonPath('data.meta.per_page', 5)
            ->assertJsonCount(1, 'data.employees');
    }

    public function test_admin_can_update_and_deactivate_employee(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();
        $branch = Branch::factory()->create();
        $supervisor = Employee::factory()->for($department)->for($position)->for($branch)->create([
            'employee_id' => 'EMP-3000',
        ]);
        $employee = Employee::factory()->for($department)->for($position)->for($branch)->create([
            'employee_id' => 'EMP-3001',
            'employment_status' => Employee::STATUS_ACTIVE,
        ]);

        $update = $this->putJson('/api/employees/'.$employee->id, [
            'employee_id' => 'EMP-3001',
            'full_name' => 'Updated Employee',
            'email' => 'updated.employee@example.com',
            'nik_ktp' => '3171010101013001',
            'npwp' => '02.300.300.1-001.000',
            'bpjs_kesehatan_number' => '0003000000001',
            'bpjs_ketenagakerjaan_number' => '30000000001',
            'tax_marital_status' => Employee::TAX_STATUS_SINGLE,
            'tax_dependents' => 0,
            'bank_name' => 'Mandiri',
            'bank_account_number' => '5555555555',
            'bank_account_holder_name' => 'Updated Employee',
            'branch_id' => $branch->id,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'supervisor_id' => $supervisor->id,
            'join_date' => '2024-02-01',
            'employment_status' => Employee::STATUS_ACTIVE,
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWTT,
            'contract_start_date' => '2024-02-01',
            'contract_end_date' => null,
            'basic_salary' => 17500000,
            'user_id' => null,
        ]);

        $update
            ->assertOk()
            ->assertJsonPath('data.employee.full_name', 'Updated Employee')
            ->assertJsonPath('data.employee.bank_name', 'Mandiri')
            ->assertJsonPath('data.employee.supervisor.employee_id', 'EMP-3000')
            ->assertJsonPath('data.employee.basic_salary', '17500000.00');

        $delete = $this->deleteJson('/api/employees/'.$employee->id);

        $delete
            ->assertOk()
            ->assertJsonPath('message', 'Employee deactivated successfully')
            ->assertJsonPath('data.employee.employment_status', Employee::STATUS_INACTIVE);
    }

    public function test_employee_role_cannot_manage_employees(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_EMPLOYEE]));

        $this->getJson('/api/employees')
            ->assertForbidden()
            ->assertJsonPath('message', 'Forbidden');
    }

    public function test_position_must_belong_to_selected_department(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $department = Department::factory()->create();
        $otherDepartment = Department::factory()->create();
        $position = Position::factory()->for($otherDepartment)->create();

        $this->postJson('/api/employees', [
            'employee_id' => 'EMP-4001',
            'full_name' => 'Wrong Position',
            'email' => 'wrong.position@example.com',
            'nik_ktp' => null,
            'npwp' => null,
            'bpjs_kesehatan_number' => null,
            'bpjs_ketenagakerjaan_number' => null,
            'tax_marital_status' => null,
            'tax_dependents' => 0,
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_holder_name' => null,
            'branch_id' => null,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'supervisor_id' => null,
            'join_date' => '2024-03-01',
            'employment_status' => Employee::STATUS_ACTIVE,
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWTT,
            'contract_start_date' => null,
            'contract_end_date' => null,
            'basic_salary' => 10000000,
            'user_id' => null,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['position_id'], 'errors');
    }

    public function test_organization_lookups_are_available_to_hr(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_HR]));

        $branch = Branch::factory()->create(['name' => 'Surabaya Outlet', 'code' => 'SBY']);
        $department = Department::factory()->create(['name' => 'Operations']);
        $position = Position::factory()->for($department)->create(['name' => 'Operations Lead']);
        Position::factory()->create(['name' => 'Unrelated Role']);
        $supervisor = Employee::factory()->for($department)->for($position)->for($branch)->create([
            'employee_id' => 'EMP-9001',
            'full_name' => 'Supervisor Lookup',
        ]);

        $this->getJson('/api/departments')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Operations']);

        $this->getJson('/api/branches')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Surabaya Outlet']);

        $this->getJson('/api/positions?department_id='.$department->id)
            ->assertOk()
            ->assertJsonCount(1, 'data.positions')
            ->assertJsonPath('data.positions.0.name', 'Operations Lead');

        $this->getJson('/api/employees/supervisors')
            ->assertOk()
            ->assertJsonFragment(['full_name' => 'Supervisor Lookup']);

        $this->getJson('/api/employees/supervisors?exclude_id='.$supervisor->id)
            ->assertOk()
            ->assertJsonMissing(['full_name' => 'Supervisor Lookup']);
    }

    public function test_employee_cannot_be_their_own_supervisor(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();
        $employee = Employee::factory()->for($department)->for($position)->create([
            'employee_id' => 'EMP-5001',
        ]);

        $this->putJson('/api/employees/'.$employee->id, [
            'employee_id' => 'EMP-5001',
            'full_name' => 'Self Supervisor',
            'email' => 'self.supervisor@example.com',
            'nik_ktp' => null,
            'npwp' => null,
            'bpjs_kesehatan_number' => null,
            'bpjs_ketenagakerjaan_number' => null,
            'tax_marital_status' => null,
            'tax_dependents' => 0,
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_holder_name' => null,
            'branch_id' => null,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'supervisor_id' => $employee->id,
            'join_date' => '2024-03-01',
            'employment_status' => Employee::STATUS_ACTIVE,
            'employment_type' => Employee::EMPLOYMENT_TYPE_PKWTT,
            'contract_start_date' => null,
            'contract_end_date' => null,
            'basic_salary' => 10000000,
            'user_id' => null,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['supervisor_id'], 'errors');
    }

    public function test_indonesian_employee_master_data_is_validated(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_ADMIN]));

        $department = Department::factory()->create();
        $position = Position::factory()->for($department)->create();

        $this->postJson('/api/employees', [
            'employee_id' => 'EMP-6001',
            'full_name' => 'Invalid Indonesia Data',
            'email' => 'invalid.indonesia@example.com',
            'nik_ktp' => '123',
            'npwp' => null,
            'bpjs_kesehatan_number' => null,
            'bpjs_ketenagakerjaan_number' => null,
            'tax_marital_status' => 'M',
            'tax_dependents' => 4,
            'bank_name' => null,
            'bank_account_number' => null,
            'bank_account_holder_name' => null,
            'branch_id' => null,
            'department_id' => $department->id,
            'position_id' => $position->id,
            'supervisor_id' => null,
            'join_date' => '2024-03-01',
            'employment_status' => Employee::STATUS_ACTIVE,
            'employment_type' => 'contractor',
            'contract_start_date' => '2024-03-01',
            'contract_end_date' => '2024-02-29',
            'basic_salary' => 10000000,
            'user_id' => null,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'nik_ktp',
                'tax_marital_status',
                'tax_dependents',
                'employment_type',
                'contract_end_date',
            ], 'errors');
    }
}
