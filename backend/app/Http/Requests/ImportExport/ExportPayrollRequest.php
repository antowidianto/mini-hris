<?php

namespace App\Http\Requests\ImportExport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportPayrollRequest extends FormRequest
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
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('company_id', $this->user()->companyId())],
        ];
    }
}
