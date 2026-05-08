<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\EmployeeContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeContract>
 */
class EmployeeContractFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d');
        $company = Company::default();

        return [
            'company_id' => $company,
            'employee_id' => Employee::factory()->for($company),
            'employment_type' => fake()->randomElement(Employee::EMPLOYMENT_TYPES),
            'contract_start_date' => $startDate,
            'contract_end_date' => fake()->dateTimeBetween($startDate, '+1 year')->format('Y-m-d'),
            'renewal_date' => now()->toDateString(),
            'document_path' => null,
            'notes' => fake()->sentence(),
            'created_by' => null,
        ];
    }
}
