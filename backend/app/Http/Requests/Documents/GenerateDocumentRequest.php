<?php

namespace App\Http\Requests\Documents;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateDocumentRequest extends FormRequest
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
        $companyId = $this->user()?->companyId();

        return [
            'employee_id' => ['required', 'integer', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'payroll_id' => ['nullable', 'integer', Rule::exists('payrolls', 'id')->where('company_id', $companyId)],
            'type' => ['required', Rule::in(Document::GENERATED_TYPES)],
            'title' => ['nullable', 'string', 'max:255'],
            'document_number' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('documents', 'document_number')->where('company_id', $companyId),
            ],
            'issue_date' => ['nullable', 'date'],
            'effective_date' => ['nullable', 'date'],
            'warning_level' => ['nullable', Rule::in(['SP1', 'SP2', 'SP3'])],
            'notes' => ['nullable', 'string', 'max:4000'],
            'signer_name' => ['nullable', 'string', 'max:255'],
            'signer_title' => ['nullable', 'string', 'max:255'],
        ];
    }
}
