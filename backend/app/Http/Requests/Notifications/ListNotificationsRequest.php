<?php

namespace App\Http\Requests\Notifications;

use App\Models\Notification;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListNotificationsRequest extends FormRequest
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
            'type' => ['nullable', Rule::in(Notification::TYPES)],
            'severity' => ['nullable', Rule::in(Notification::SEVERITIES)],
            'unread' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ];
    }
}
