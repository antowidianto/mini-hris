<?php

namespace App\Http\Resources;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'type_label' => $this->typeLabel($this->type),
            'source' => $this->source,
            'title' => $this->title,
            'document_number' => $this->document_number,
            'issue_date' => $this->issue_date?->format('Y-m-d'),
            'original_file_name' => $this->original_file_name,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'metadata' => $this->metadata,
            'generated_at' => $this->generated_at?->toISOString(),
            'employee' => new EmployeeResource($this->whenLoaded('employee')),
            'payroll' => new PayrollResource($this->whenLoaded('payroll')),
            'generator' => new AuthUserResource($this->whenLoaded('generator')),
            'preview_url' => url("/api/documents/{$this->id}/preview"),
            'download_url' => url("/api/documents/{$this->id}/download"),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }

    private function typeLabel(string $type): string
    {
        return [
            Document::TYPE_EMPLOYEE_FILE => 'Employee File',
            Document::TYPE_PAYSLIP => 'Payslip',
            Document::TYPE_EMPLOYMENT_CERTIFICATE => 'Employment Certificate',
            Document::TYPE_CONTRACT_LETTER => 'Contract Letter',
            Document::TYPE_WARNING_LETTER => 'Warning Letter',
        ][$type] ?? $type;
    }
}
