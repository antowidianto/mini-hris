<?php

namespace App\Http\Requests\CompanySettings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCompanySettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'max:255'],
            'logo_path' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'company_npwp' => ['nullable', 'string', 'max:30'],
            'default_work_start' => ['required', 'date_format:H:i'],
            'default_work_end' => ['required', 'date_format:H:i', 'after:default_work_start'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:240'],
            'annual_leave_quota' => ['required', 'integer', 'min:0', 'max:60'],
            'payroll_work_days_per_month' => ['required', 'integer', 'min:1', 'max:31'],
            'late_deduction_amount' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'bpjs_kesehatan_employee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'bpjs_kesehatan_employer_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'bpjs_jht_employee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'bpjs_jht_employer_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'bpjs_jp_employee_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'bpjs_jp_employer_rate' => ['required', 'numeric', 'min:0', 'max:100'],
            'payroll_fixed_allowance_default' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'payroll_non_fixed_allowance_default' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'meal_allowance_default' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'transport_allowance_default' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'pph21_default_deduction' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'employee_number_format' => ['required', 'string', 'max:100'],
        ];
    }
}
