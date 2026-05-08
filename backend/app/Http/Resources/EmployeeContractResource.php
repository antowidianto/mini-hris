<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeContractResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employment_type' => $this->employment_type,
            'contract_start_date' => $this->contract_start_date?->format('Y-m-d'),
            'contract_end_date' => $this->contract_end_date?->format('Y-m-d'),
            'renewal_date' => $this->renewal_date?->format('Y-m-d'),
            'document_path' => $this->document_path,
            'notes' => $this->notes,
            'creator' => new AuthUserResource($this->whenLoaded('creator')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
