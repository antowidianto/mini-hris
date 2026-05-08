<?php

namespace App\Http\Requests\Contracts;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreEmployeeContractRequest extends FormRequest
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
            'employment_type' => ['required', Rule::in(Employee::EMPLOYMENT_TYPES)],
            'contract_start_date' => ['required', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'renewal_date' => ['nullable', 'date'],
            'document_path' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if (
                    in_array($this->input('employment_type'), [
                        Employee::EMPLOYMENT_TYPE_PROBATION,
                        Employee::EMPLOYMENT_TYPE_PKWT,
                    ], true)
                    && ! $this->filled('contract_end_date')
                ) {
                    $validator->errors()->add('contract_end_date', 'Contract end date is required for probation and PKWT contracts.');
                }
            },
        ];
    }
}
