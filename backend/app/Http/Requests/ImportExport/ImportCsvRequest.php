<?php

namespace App\Http\Requests\ImportExport;

use Illuminate\Foundation\Http\FormRequest;

class ImportCsvRequest extends FormRequest
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
            'file' => ['required', 'file', 'max:10240', 'mimes:csv,txt'],
            'source' => ['nullable', 'string', 'max:50'],
        ];
    }
}
