<?php

namespace App\Http\Requests\Documents;

use App\Models\Document;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListDocumentsRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:255'],
            'employee_id' => ['nullable', 'integer', Rule::exists('employees', 'id')->where('company_id', $companyId)],
            'type' => ['nullable', Rule::in(Document::TYPES)],
            'source' => ['nullable', Rule::in(Document::SOURCES)],
            'issue_date_from' => ['nullable', 'date'],
            'issue_date_to' => ['nullable', 'date', 'after_or_equal:issue_date_from'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ];
    }
}
