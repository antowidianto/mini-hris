<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContractExpiryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'employment_type' => $this->employment_type,
            'contract_start_date' => $this->contract_start_date?->format('Y-m-d'),
            'contract_end_date' => $this->contract_end_date?->format('Y-m-d'),
            'days_remaining' => $this->contract_end_date
                ? now()->startOfDay()->diffInDays($this->contract_end_date, false)
                : null,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'position' => new PositionResource($this->whenLoaded('position')),
            'supervisor' => new EmployeeSupervisorResource($this->whenLoaded('supervisor')),
        ];
    }
}
