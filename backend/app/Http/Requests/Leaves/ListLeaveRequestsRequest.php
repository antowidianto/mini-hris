<?php

namespace App\Http\Requests\Leaves;

use App\Models\LeaveRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListLeaveRequestsRequest extends FormRequest
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
            'status' => ['nullable', Rule::in(LeaveRequest::STATUSES)],
            'supervisor_status' => ['nullable', Rule::in(LeaveRequest::DECISIONS)],
            'hr_status' => ['nullable', Rule::in(LeaveRequest::DECISIONS)],
            'leave_type_id' => ['nullable', 'integer', 'exists:leave_types,id'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ];
    }
}
