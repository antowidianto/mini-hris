<?php

namespace App\Http\Requests\AuditLogs;

use App\Models\AuditLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListAuditLogsRequest extends FormRequest
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
            'module' => ['nullable', 'string', Rule::in(AuditLog::MODULES)],
            'action' => ['nullable', 'string', Rule::in(AuditLog::ACTIONS)],
            'user_id' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where('company_id', $this->user()?->companyId()),
            ],
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
