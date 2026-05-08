<?php

namespace App\Http\Requests\Payroll;

use Illuminate\Foundation\Http\FormRequest;

class ListPayrollRequest extends FormRequest
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
            'period_year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'period_month' => ['nullable', 'integer', 'min:1', 'max:12'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ];
    }
}
