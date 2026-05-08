<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ImportJobResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'file_name' => $this->file_name,
            'status' => $this->status,
            'total_rows' => $this->total_rows,
            'success_rows' => $this->success_rows,
            'failed_rows' => $this->failed_rows,
            'summary' => $this->summary,
            'failures' => $this->failures,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
