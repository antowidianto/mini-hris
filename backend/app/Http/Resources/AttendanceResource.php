<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attendance_date' => $this->attendance_date?->format('Y-m-d'),
            'shift_start' => $this->shift_start,
            'shift_end' => $this->shift_end,
            'late_tolerance_minutes' => $this->late_tolerance_minutes,
            'time_in' => $this->time_in,
            'time_out' => $this->time_out,
            'overtime_minutes' => $this->overtime_minutes,
            'status' => $this->status,
            'attendance_source' => $this->attendance_source,
            'import_batch' => $this->import_batch,
            'notes' => $this->notes,
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
