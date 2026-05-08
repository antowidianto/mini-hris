<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('+1 week', '+2 months');
        $endDate = (clone $startDate)->modify('+1 day');
        $company = Company::default();

        return [
            'company_id' => $company,
            'employee_id' => Employee::factory()->for($company),
            'leave_type_id' => LeaveType::factory()->for($company),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'total_days' => 2,
            'reason' => fake()->sentence(),
            'status' => LeaveRequest::STATUS_PENDING,
            'supervisor_status' => LeaveRequest::DECISION_APPROVED,
            'supervisor_notes' => null,
            'supervisor_approved_by' => null,
            'supervisor_approved_at' => null,
            'hr_status' => LeaveRequest::DECISION_PENDING,
            'hr_notes' => null,
            'hr_approved_by' => null,
            'hr_approved_at' => null,
            'approval_notes' => null,
            'approved_by' => null,
            'approved_at' => null,
        ];
    }
}
