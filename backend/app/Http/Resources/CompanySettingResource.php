<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanySettingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'logo_path' => $this->logo_path,
            'address' => $this->address,
            'company_npwp' => $this->company_npwp,
            'default_work_start' => substr((string) $this->default_work_start, 0, 5),
            'default_work_end' => substr((string) $this->default_work_end, 0, 5),
            'late_tolerance_minutes' => $this->late_tolerance_minutes,
            'annual_leave_quota' => $this->annual_leave_quota,
            'payroll_work_days_per_month' => $this->payroll_work_days_per_month,
            'late_deduction_amount' => $this->late_deduction_amount,
            'bpjs_kesehatan_employee_rate' => $this->bpjs_kesehatan_employee_rate,
            'bpjs_kesehatan_employer_rate' => $this->bpjs_kesehatan_employer_rate,
            'bpjs_jht_employee_rate' => $this->bpjs_jht_employee_rate,
            'bpjs_jht_employer_rate' => $this->bpjs_jht_employer_rate,
            'bpjs_jp_employee_rate' => $this->bpjs_jp_employee_rate,
            'bpjs_jp_employer_rate' => $this->bpjs_jp_employer_rate,
            'payroll_fixed_allowance_default' => $this->payroll_fixed_allowance_default,
            'payroll_non_fixed_allowance_default' => $this->payroll_non_fixed_allowance_default,
            'meal_allowance_default' => $this->meal_allowance_default,
            'transport_allowance_default' => $this->transport_allowance_default,
            'pph21_default_deduction' => $this->pph21_default_deduction,
            'employee_number_format' => $this->employee_number_format,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
