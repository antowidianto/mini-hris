<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
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
            'code' => fake()->unique()->lexify('LV???'),
            'name' => fake()->words(2, true),
            'annual_entitlement' => 12,
            'is_paid' => true,
            'is_active' => true,
        ];
    }
}
