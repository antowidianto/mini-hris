<?php

namespace App\Http\Requests\Employees;

use App\Models\Employee;
use App\Models\Position;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateEmployeeRequest extends FormRequest
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
        /** @var Employee $employee */
        $employee = $this->route('employee');
        $companyId = $this->user()->companyId();

        return [
            'employee_id' => [
                'required',
                'string',
                'max:50',
                Rule::unique('employees', 'employee_id')->where('company_id', $companyId)->ignore($employee),
            ],
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('employees', 'email')->ignore($employee)],
            'nik_ktp' => ['nullable', 'digits:16', Rule::unique('employees', 'nik_ktp')->ignore($employee)],
            'npwp' => ['nullable', 'string', 'max:30', Rule::unique('employees', 'npwp')->ignore($employee)],
            'bpjs_kesehatan_number' => ['nullable', 'string', 'max:30', Rule::unique('employees', 'bpjs_kesehatan_number')->ignore($employee)],
            'bpjs_ketenagakerjaan_number' => ['nullable', 'string', 'max:30', Rule::unique('employees', 'bpjs_ketenagakerjaan_number')->ignore($employee)],
            'tax_marital_status' => ['nullable', Rule::in(Employee::TAX_STATUSES)],
            'tax_dependents' => ['required', 'integer', 'min:0', 'max:3'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:50'],
            'bank_account_holder_name' => ['nullable', 'string', 'max:255'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'position_id' => ['required', 'integer', 'exists:positions,id'],
            'supervisor_id' => ['nullable', 'integer', 'exists:employees,id'],
            'join_date' => ['required', 'date'],
            'employment_status' => ['required', Rule::in(Employee::STATUSES)],
            'employment_type' => ['required', Rule::in(Employee::EMPLOYMENT_TYPES)],
            'contract_start_date' => ['nullable', 'date'],
            'contract_end_date' => ['nullable', 'date', 'after_or_equal:contract_start_date'],
            'basic_salary' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'user_id' => ['nullable', 'integer', 'exists:users,id', Rule::unique('employees', 'user_id')->ignore($employee)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                $position = Position::query()->find($this->integer('position_id'));

                if ($position && $position->department_id !== $this->integer('department_id')) {
                    $validator->errors()->add('position_id', 'The selected position does not belong to the selected department.');
                }

                /** @var Employee $employee */
                $employee = $this->route('employee');

                if ($this->integer('supervisor_id') === $employee->id) {
                    $validator->errors()->add('supervisor_id', 'An employee cannot be their own direct supervisor.');
                }
            },
        ];
    }
}
