<?php

namespace App\Http\Requests\ImportExport;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExportEmployeesRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'employment_status' => ['nullable', Rule::in(Employee::STATUSES)],
            'employment_type' => ['nullable', Rule::in(Employee::EMPLOYMENT_TYPES)],
        ];
    }
}
