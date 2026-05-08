<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::default();
        $department = Department::factory()->for($company);

        return [
            'user_id' => null,
            'company_id' => $company,
            'branch_id' => null,
            'department_id' => $department,
            'position_id' => Position::factory()->for($company)->for($department),
            'supervisor_id' => null,
            'employee_id' => fake()->unique()->numerify('EMP-####'),
            'full_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'nik_ktp' => fake()->unique()->numerify('################'),
            'npwp' => fake()->unique()->numerify('##.###.###.#-###.###'),
            'bpjs_kesehatan_number' => fake()->unique()->numerify('#############'),
            'bpjs_ketenagakerjaan_number' => fake()->unique()->numerify('###########'),
            'tax_marital_status' => fake()->randomElement(Employee::TAX_STATUSES),
            'tax_dependents' => fake()->numberBetween(0, 3),
            'bank_name' => fake()->randomElement(['BCA', 'Mandiri', 'BRI', 'BNI']),
            'bank_account_number' => fake()->unique()->numerify('##########'),
            'bank_account_holder_name' => fake()->name(),
            'join_date' => fake()->dateTimeBetween('-5 years', 'now')->format('Y-m-d'),
            'employment_status' => Employee::STATUS_ACTIVE,
            'employment_type' => fake()->randomElement(Employee::EMPLOYMENT_TYPES),
            'contract_start_date' => fake()->dateTimeBetween('-2 years', 'now')->format('Y-m-d'),
            'contract_end_date' => null,
            'basic_salary' => fake()->numberBetween(5_000_000, 30_000_000),
        ];
    }
}
