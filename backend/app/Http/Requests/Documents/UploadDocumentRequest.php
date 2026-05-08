<?php

namespace App\Http\Requests\Documents;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UploadDocumentRequest extends FormRequest
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
            'type' => ['required', Rule::in(Document::TYPES)],
            'title' => ['required', 'string', 'max:255'],
            'document_number' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('documents', 'document_number')->where('company_id', $companyId),
            ],
            'issue_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:4000'],
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,doc,docx'],
        ];
    }
}
