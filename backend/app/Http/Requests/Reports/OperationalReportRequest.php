<?php

namespace App\Http\Requests\Reports;

use App\Models\Employee;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class OperationalReportRequest extends FormRequest
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
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'branch_id' => [
                'nullable',
                'integer',
                Rule::exists('branches', 'id')->where('company_id', $this->user()->companyId()),
            ],
            'department_id' => [
                'nullable',
                'integer',
                Rule::exists('departments', 'id')->where('company_id', $this->user()->companyId()),
            ],
            'employment_status' => ['nullable', Rule::in(Employee::STATUSES)],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('date_from') || $validator->errors()->has('date_to')) {
                    return;
                }

                $dateFrom = $this->filled('date_from')
                    ? Carbon::parse((string) $this->input('date_from'))
                    : now()->startOfMonth();
                $dateTo = $this->filled('date_to')
                    ? Carbon::parse((string) $this->input('date_to'))
                    : now();

                if ($dateFrom->diffInDays($dateTo) > 366) {
                    $validator->errors()->add('date_to', 'Operational reports are limited to a 366 day date range.');
                }
            },
        ];
    }
}
