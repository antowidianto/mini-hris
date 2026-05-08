<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalStepResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'module' => $this->module,
            'step_order' => $this->step_order,
            'role' => $this->role,
            'status' => $this->status,
            'notes' => $this->notes,
            'approved_at' => $this->approved_at?->toISOString(),
            'approver' => new AuthUserResource($this->whenLoaded('approver')),
        ];
    }
}
