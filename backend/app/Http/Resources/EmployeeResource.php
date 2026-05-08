<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class EmployeeResource extends JsonResource
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
            'nik_ktp' => $this->nik_ktp,
            'npwp' => $this->npwp,
            'bpjs_kesehatan_number' => $this->bpjs_kesehatan_number,
            'bpjs_ketenagakerjaan_number' => $this->bpjs_ketenagakerjaan_number,
            'tax_marital_status' => $this->tax_marital_status,
            'tax_dependents' => $this->tax_dependents,
            'bank_name' => $this->bank_name,
            'bank_account_number' => $this->bank_account_number,
            'bank_account_holder_name' => $this->bank_account_holder_name,
            'join_date' => $this->join_date?->format('Y-m-d'),
            'employment_status' => $this->employment_status,
            'employment_type' => $this->employment_type,
            'contract_start_date' => $this->contract_start_date?->format('Y-m-d'),
            'contract_end_date' => $this->contract_end_date?->format('Y-m-d'),
            'basic_salary' => $this->basic_salary,
            'branch' => new BranchResource($this->whenLoaded('branch')),
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'position' => new PositionResource($this->whenLoaded('position')),
            'supervisor' => new EmployeeSupervisorResource($this->whenLoaded('supervisor')),
            'user' => new AuthUserResource($this->whenLoaded('user')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
