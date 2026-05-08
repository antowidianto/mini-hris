<?php

namespace App\Http\Requests\ImportExport;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportAttendanceRecapRequest extends FormRequest
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
            'year' => ['required', 'integer', 'min:2020', 'max:2100'],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('company_id', $this->user()->companyId())],
            'department_id' => ['nullable', 'integer', Rule::exists('departments', 'id')->where('company_id', $this->user()->companyId())],
        ];
    }
}
