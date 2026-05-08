<?php

namespace App\Http\Resources;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $employee = $this->employee()
            ->where('employment_status', Employee::STATUS_ACTIVE)
            ->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'employee' => $employee ? [
                'id' => $employee->id,
                'employee_id' => $employee->employee_id,
                'full_name' => $employee->full_name,
                'has_direct_reports' => $employee->directReports()
                    ->where('employment_status', Employee::STATUS_ACTIVE)
                    ->exists(),
            ] : null,
        ];
    }
}
