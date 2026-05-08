<?php

namespace Tests\Feature\Documents;

use App\Models\Document;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DocumentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_generate_and_preview_employment_certificate_pdf(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $employee = Employee::factory()->create(['company_id' => $hr->company_id]);

        Sanctum::actingAs($hr);

        $this->postJson('/api/documents/generate', [
            'employee_id' => $employee->id,
            'type' => Document::TYPE_EMPLOYMENT_CERTIFICATE,
            'document_number' => 'SKK/2026/001',
            'issue_date' => '2026-05-06',
            'signer_name' => 'HR Manager',
        ])
            ->assertCreated()
            ->assertJsonPath('data.document.type', Document::TYPE_EMPLOYMENT_CERTIFICATE)
            ->assertJsonPath('data.document.employee.id', $employee->id);

        $document = Document::query()->firstOrFail();

        $this->getJson('/api/documents')
            ->assertOk()
            ->assertJsonCount(1, 'data.documents')
            ->assertJsonPath('data.documents.0.document_number', 'SKK/2026/001');

        $this->get("/api/documents/{$document->id}/preview")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_payslip_generation_requires_matching_employee_payroll(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $employee = Employee::factory()->create(['company_id' => $hr->company_id]);
        $otherEmployee = Employee::factory()->create(['company_id' => $hr->company_id]);
        $payroll = Payroll::factory()->for($otherEmployee)->create(['company_id' => $hr->company_id]);

        Sanctum::actingAs($hr);

        $this->postJson('/api/documents/generate', [
            'employee_id' => $employee->id,
            'type' => Document::TYPE_PAYSLIP,
            'payroll_id' => $payroll->id,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['payroll_id'], 'errors');
    }

    public function test_hr_can_upload_employee_document_file(): void
    {
        Storage::fake('local');

        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $employee = Employee::factory()->create(['company_id' => $hr->company_id]);
        $file = UploadedFile::fake()->create('ktp.pdf', 64, 'application/pdf');

        Sanctum::actingAs($hr);

        $this->postJson('/api/documents/upload', [
            'employee_id' => $employee->id,
            'type' => Document::TYPE_EMPLOYEE_FILE,
            'title' => 'KTP Scan',
            'issue_date' => '2026-05-06',
            'file' => $file,
        ])
            ->assertCreated()
            ->assertJsonPath('data.document.source', Document::SOURCE_UPLOADED)
            ->assertJsonPath('data.document.original_file_name', 'ktp.pdf')
            ->assertJsonMissingPath('data.document.file_path');

        $document = Document::query()->firstOrFail();

        Storage::disk('local')->assertExists($document->file_path);
    }

    public function test_document_number_must_be_unique_inside_company(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $employee = Employee::factory()->create(['company_id' => $hr->company_id]);

        Document::query()->create([
            'company_id' => $hr->company_id,
            'employee_id' => $employee->id,
            'type' => Document::TYPE_EMPLOYMENT_CERTIFICATE,
            'source' => Document::SOURCE_GENERATED,
            'title' => 'Existing Certificate',
            'document_number' => 'DOC/001',
            'issue_date' => '2026-05-06',
        ]);

        Sanctum::actingAs($hr);

        $this->postJson('/api/documents/generate', [
            'employee_id' => $employee->id,
            'type' => Document::TYPE_EMPLOYMENT_CERTIFICATE,
            'document_number' => 'DOC/001',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['document_number'], 'errors');
    }

    public function test_employee_can_only_list_own_documents(): void
    {
        $employee = Employee::factory()
            ->for(User::factory()->create(['role' => User::ROLE_EMPLOYEE]))
            ->create();
        $otherEmployee = Employee::factory()->create(['company_id' => $employee->company_id]);

        Document::query()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $employee->id,
            'type' => Document::TYPE_EMPLOYMENT_CERTIFICATE,
            'source' => Document::SOURCE_GENERATED,
            'title' => 'Own Certificate',
            'issue_date' => '2026-05-06',
        ]);
        Document::query()->create([
            'company_id' => $employee->company_id,
            'employee_id' => $otherEmployee->id,
            'type' => Document::TYPE_EMPLOYMENT_CERTIFICATE,
            'source' => Document::SOURCE_GENERATED,
            'title' => 'Other Certificate',
            'issue_date' => '2026-05-06',
        ]);

        Sanctum::actingAs($employee->user);

        $this->getJson('/api/documents/my')
            ->assertOk()
            ->assertJsonCount(1, 'data.documents')
            ->assertJsonPath('data.documents.0.title', 'Own Certificate');

        $this->getJson('/api/documents')
            ->assertForbidden();
    }
}
