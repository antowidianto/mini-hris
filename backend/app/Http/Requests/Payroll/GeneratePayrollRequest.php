<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class GeneratePayrollRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'fixed_allowance' => $this->input('fixed_allowance', $this->input('allowance')),
            'other_deduction' => $this->input('other_deduction', $this->input('deduction')),
        ]);
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        return [
            'period_year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'period_month' => ['required', 'integer', 'min:1', 'max:12'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'fixed_allowance' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'non_fixed_allowance' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'meal_allowance' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'transport_allowance' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'pph21_deduction' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'other_deduction' => ['nullable', 'numeric', 'min:0', 'max:999999999'],
        ];
    }
}
