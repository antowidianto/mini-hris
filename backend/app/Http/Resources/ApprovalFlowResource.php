<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApprovalFlowResource extends JsonResource
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
            'is_active' => $this->is_active,
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
