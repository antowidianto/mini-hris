<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Payroll>
 */
class PayrollFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $basicSalary = fake()->numberBetween(8_000_000, 25_000_000);
        $fixedAllowance = 500_000;
        $nonFixedAllowance = 0;
        $mealAllowance = 0;
        $transportAllowance = 0;
        $allowance = $fixedAllowance + $nonFixedAllowance + $mealAllowance + $transportAllowance;
        $grossSalary = $basicSalary + $allowance;
        $otherDeduction = 0;
        $attendanceDeduction = 0;
        $lateDeduction = 0;
        $unpaidLeaveDeduction = 0;
        $totalEmployeeBpjs = 0;
        $totalEmployerBpjs = 0;
        $pph21Deduction = 0;
        $takeHomePay = $grossSalary - $otherDeduction - $attendanceDeduction - $lateDeduction - $unpaidLeaveDeduction - $totalEmployeeBpjs - $pph21Deduction;
        $company = Company::default();

        return [
            'company_id' => $company,
            'employee_id' => Employee::factory()->for($company),
            'period_year' => now()->year,
            'period_month' => now()->month,
            'basic_salary' => $basicSalary,
            'fixed_allowance' => $fixedAllowance,
            'non_fixed_allowance' => $nonFixedAllowance,
            'meal_allowance' => $mealAllowance,
            'transport_allowance' => $transportAllowance,
            'allowance' => $allowance,
            'gross_salary' => $grossSalary,
            'deduction' => $otherDeduction,
            'attendance_deduction' => $attendanceDeduction,
            'late_deduction' => $lateDeduction,
            'unpaid_leave_deduction' => $unpaidLeaveDeduction,
            'bpjs_kesehatan_employee' => 0,
            'bpjs_kesehatan_employer' => 0,
            'bpjs_jht_employee' => 0,
            'bpjs_jht_employer' => 0,
            'bpjs_jp_employee' => 0,
            'bpjs_jp_employer' => 0,
            'pph21_deduction' => $pph21Deduction,
            'other_deduction' => $otherDeduction,
            'total_employee_bpjs' => $totalEmployeeBpjs,
            'total_employer_bpjs' => $totalEmployerBpjs,
            'net_salary' => $takeHomePay,
            'take_home_pay' => $takeHomePay,
            'absent_days' => 0,
            'late_days' => 0,
            'unpaid_leave_days' => 0,
            'settings_snapshot' => null,
            'generated_by' => User::factory(),
            'generated_at' => now(),
        ];
    }
}
