<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterTenantRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'company_code' => $this->company_code ? str($this->company_code)->upper()->toString() : null,
            'plan' => $this->plan ? str($this->plan)->lower()->toString() : null,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'company_name' => ['bail', 'required', 'string', 'max:255'],
            'company_code' => ['bail', 'required', 'alpha_dash', 'max:30', Rule::unique('companies', 'code')],
            'billing_email' => ['bail', 'required', 'email', 'max:255', Rule::unique('users', 'email')],
            'admin_name' => ['bail', 'required', 'string', 'max:255'],
            'password' => ['bail', 'required', 'confirmed', Password::defaults()],
            'plan' => ['bail', 'nullable', 'string', Rule::in(['starter', 'growth', 'scale'])],
            'address' => ['bail', 'nullable', 'string', 'max:1000'],
        ];
    }
}
