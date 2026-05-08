<?php

namespace App\Http\Requests\Positions;

use Illuminate\Foundation\Http\FormRequest;

class ListPositionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ];
    }
}
