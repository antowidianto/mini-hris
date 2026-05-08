<?php

namespace App\Http\Requests\Settings;

use App\Models\ApprovalFlow;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

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

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $activeSteps = [];
            $activeModules = [];
            $leaveHasManagementStep = false;

            $flows = $this->input('flows', []);

            if (! is_array($flows)) {
                return;
            }

            foreach ($flows as $index => $flow) {
                if (! is_array($flow)) {
                    continue;
                }

                if (! ($flow['is_active'] ?? false)) {
                    continue;
                }

                $module = $flow['module'] ?? null;
                $stepOrder = $flow['step_order'] ?? null;

                if (! $module || ! $stepOrder) {
                    continue;
                }

                $stepKey = "{$module}:{$stepOrder}";
                $role = $flow['role'] ?? null;

                if (isset($activeSteps[$stepKey])) {
                    $validator->errors()->add(
                        "flows.{$index}.step_order",
                        'Only one active approval role is allowed for each module step.'
                    );
                }

                $activeSteps[$stepKey] = true;
                $activeModules[$module] = true;

                if ($module === ApprovalFlow::MODULE_LEAVE) {
                    if (! in_array($role, ['supervisor', User::ROLE_ADMIN, User::ROLE_HR], true)) {
                        $validator->errors()->add(
                            "flows.{$index}.role",
                            'Leave approval steps can only use supervisor, admin, or HR roles.'
                        );
                    }

                    if (in_array($role, [User::ROLE_ADMIN, User::ROLE_HR], true)) {
                        $leaveHasManagementStep = true;
                    }
                }

                if ($module === ApprovalFlow::MODULE_PAYROLL && ! in_array($role, [User::ROLE_ADMIN, User::ROLE_HR], true)) {
                    $validator->errors()->add(
                        "flows.{$index}.role",
                        'Payroll approval steps can only use admin or HR roles.'
                    );
                }
            }

            foreach ([ApprovalFlow::MODULE_LEAVE, ApprovalFlow::MODULE_PAYROLL] as $requiredModule) {
                if (! isset($activeModules[$requiredModule])) {
                    $validator->errors()->add(
                        'flows',
                        ucfirst($requiredModule).' approval flow must have at least one active step.'
                    );
                }
            }

            if (isset($activeModules[ApprovalFlow::MODULE_LEAVE]) && ! $leaveHasManagementStep) {
                $validator->errors()->add(
                    'flows',
                    'Leave approval flow must include at least one active admin or HR step.'
                );
            }
        });
    }
}
