<?php

namespace App\Http\Requests\Contracts;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListExpiringContractsRequest extends FormRequest
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
            'days' => ['nullable', 'integer', Rule::in([30, 60])],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ];
    }
}
