<?php

namespace App\Http\Requests\Attendance;

use Illuminate\Foundation\Http\FormRequest;

class AttendanceImportPlaceholderRequest extends FormRequest
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
            'file_name' => ['required', 'string', 'max:255'],
            'source' => ['required', 'string', 'in:fingerprint,import'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
