<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveBalance>
 */
class LeaveBalanceFactory extends Factory
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
            'employee_id' => Employee::factory()->for($company),
            'leave_type_id' => LeaveType::factory()->for($company),
            'year' => now()->year,
            'entitlement_days' => 12,
            'used_days' => 0,
        ];
    }
}
