<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LeaveRequestResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'start_date' => $this->start_date?->format('Y-m-d'),
            'end_date' => $this->end_date?->format('Y-m-d'),
            'total_days' => $this->total_days,
            'reason' => $this->reason,
            'status' => $this->status,
            'supervisor_status' => $this->supervisor_status,
            'supervisor_notes' => $this->supervisor_notes,
            'supervisor_approved_at' => $this->supervisor_approved_at?->toISOString(),
            'hr_status' => $this->hr_status,
            'hr_notes' => $this->hr_notes,
            'hr_approved_at' => $this->hr_approved_at?->toISOString(),
            'approval_notes' => $this->approval_notes,
            'approved_at' => $this->approved_at?->toISOString(),
            'approval_steps' => ApprovalStepResource::collection($this->whenLoaded('approvalSteps')),
            'current_approval_step' => new ApprovalStepResource($this->whenLoaded('approvalSteps', function () {
                return $this->approvalSteps->firstWhere('status', 'pending');
            })),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'leave_type' => new LeaveTypeResource($this->whenLoaded('leaveType')),
            'supervisor_approver' => new AuthUserResource($this->whenLoaded('supervisorApprover')),
            'hr_approver' => new AuthUserResource($this->whenLoaded('hrApprover')),
            'approver' => new AuthUserResource($this->whenLoaded('approver')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
