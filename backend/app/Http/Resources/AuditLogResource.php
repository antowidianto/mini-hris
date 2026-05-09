<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuditLogResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'action' => $this->action,
            'action_label' => $this->actionLabel(),
            'module' => $this->module,
            'module_label' => $this->moduleLabel(),
            'description' => $this->description,
            'summary' => $this->summary(),
            'ip_address' => $this->ip_address,
            'user_agent' => $this->user_agent,
            'user' => $this->whenLoaded('user', fn () => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'role' => $this->user->role,
            ] : null),
            'created_at' => $this->created_at?->toISOString(),
            'created_at_display' => $this->created_at?->format('Y-m-d H:i'),
        ];
    }

    private function summary(): string
    {
        $actor = $this->user?->name ?? 'System';

        return "{$actor} {$this->actionLabel()} {$this->moduleLabel()}";
    }
}
