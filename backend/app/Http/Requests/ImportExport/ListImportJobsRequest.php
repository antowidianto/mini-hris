<?php

namespace App\Http\Requests\ImportExport;

use App\Models\ImportJob;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListImportJobsRequest extends FormRequest
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
            'type' => ['nullable', Rule::in(ImportJob::TYPES)],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ];
    }
}
