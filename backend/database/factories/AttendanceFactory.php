<?php

namespace Database\Factories;

use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $company = Company::default();
        $employee = Employee::factory()->for($company);

        return [
            'company_id' => $company,
            'employee_id' => $employee,
            'attendance_date' => fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d'),
            'shift_start' => '08:00:00',
            'shift_end' => '17:00:00',
            'late_tolerance_minutes' => 10,
            'time_in' => '08:45:00',
            'time_out' => '17:00:00',
            'overtime_minutes' => 0,
            'status' => Attendance::STATUS_PRESENT,
            'attendance_source' => Attendance::SOURCE_MANUAL,
            'import_batch' => null,
            'notes' => null,
        ];
    }
}
