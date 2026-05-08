<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PayrollResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $grossSalary = (float) $this->gross_salary > 0
            ? $this->gross_salary
            : number_format((float) $this->basic_salary + (float) $this->allowance, 2, '.', '');
        $otherDeduction = (float) $this->other_deduction > 0
            ? $this->other_deduction
            : $this->deduction;
        $takeHomePay = (float) $this->take_home_pay > 0
            ? $this->take_home_pay
            : $this->net_salary;

        return [
            'id' => $this->id,
            'period_year' => $this->period_year,
            'period_month' => $this->period_month,
            'period_label' => sprintf('%04d-%02d', $this->period_year, $this->period_month),
            'basic_salary' => $this->basic_salary,
            'fixed_allowance' => $this->fixed_allowance,
            'non_fixed_allowance' => $this->non_fixed_allowance,
            'meal_allowance' => $this->meal_allowance,
            'transport_allowance' => $this->transport_allowance,
            'allowance' => $this->allowance,
            'gross_salary' => $grossSalary,
            'deduction' => $this->deduction,
            'attendance_deduction' => $this->attendance_deduction,
            'late_deduction' => $this->late_deduction,
            'unpaid_leave_deduction' => $this->unpaid_leave_deduction,
            'bpjs_kesehatan_employee' => $this->bpjs_kesehatan_employee,
            'bpjs_kesehatan_employer' => $this->bpjs_kesehatan_employer,
            'bpjs_jht_employee' => $this->bpjs_jht_employee,
            'bpjs_jht_employer' => $this->bpjs_jht_employer,
            'bpjs_jp_employee' => $this->bpjs_jp_employee,
            'bpjs_jp_employer' => $this->bpjs_jp_employer,
            'pph21_deduction' => $this->pph21_deduction,
            'other_deduction' => $otherDeduction,
            'total_employee_bpjs' => $this->total_employee_bpjs,
            'total_employer_bpjs' => $this->total_employer_bpjs,
            'total_deductions' => number_format(
                (float) $otherDeduction
                    + (float) $this->attendance_deduction
                    + (float) $this->late_deduction
                    + (float) $this->unpaid_leave_deduction
                    + (float) $this->total_employee_bpjs
                    + (float) $this->pph21_deduction,
                2,
                '.',
                ''
            ),
            'net_salary' => $this->net_salary,
            'take_home_pay' => $takeHomePay,
            'absent_days' => $this->absent_days,
            'late_days' => $this->late_days,
            'unpaid_leave_days' => $this->unpaid_leave_days,
            'settings_snapshot' => $this->settings_snapshot,
            'generated_at' => $this->generated_at?->toISOString(),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'generator' => new AuthUserResource($this->whenLoaded('generator')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
