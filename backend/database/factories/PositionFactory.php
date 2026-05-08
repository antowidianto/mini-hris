<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Position;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Position>
 */
class PositionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::default();

        return [
            'company_id' => $company,
            'department_id' => Department::factory()->for($company),
            'name' => fake()->unique()->jobTitle(),
            'description' => fake()->sentence(),
        ];
    }
}
