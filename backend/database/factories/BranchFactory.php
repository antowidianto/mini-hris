<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Company;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Branch>
 */
class BranchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::default(),
            'name' => fake()->city().' Branch',
            'code' => fake()->unique()->bothify('BR-###'),
            'type' => fake()->randomElement(Branch::TYPES),
            'area' => fake()->city(),
            'address' => fake()->address(),
            'is_active' => true,
        ];
    }
}
