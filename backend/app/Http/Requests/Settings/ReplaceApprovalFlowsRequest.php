<?php

namespace App\Http\Requests\Settings;

use App\Models\ApprovalFlow;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplaceApprovalFlowsRequest extends FormRequest
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
            'flows' => ['required', 'array', 'min:1'],
            'flows.*.module' => ['required', Rule::in(ApprovalFlow::MODULES)],
            'flows.*.step_order' => ['required', 'integer', 'min:1', 'max:20'],
            'flows.*.role' => ['required', Rule::in(['supervisor', User::ROLE_ADMIN, User::ROLE_HR, User::ROLE_EMPLOYEE])],
            'flows.*.is_active' => ['required', 'boolean'],
        ];
    }
}
