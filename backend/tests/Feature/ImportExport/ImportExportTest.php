<?php

namespace Tests\Feature\ImportExport;

use App\Models\Attendance;
use App\Models\Department;
use App\Models\Employee;
use App\Models\ImportJob;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ImportExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_hr_can_import_employees_from_csv_with_row_failures(): void
    {
        Storage::fake('local');

        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $file = UploadedFile::fake()->createWithContent('employees.csv', implode("\n", [
            'employee_id,full_name,email,department,position,join_date,basic_salary,employment_status,employment_type',
            'EMP-9001,Sari Dewi,sari@example.test,Finance,Staff,2026-05-01,7500000,active,pkwtt',
            'EMP-9002,,bad-email,Finance,Staff,2026-05-01,7500000,active,pkwtt',
        ]));

        Sanctum::actingAs($hr);

        $this->postJson('/api/imports/employees', [
            'file' => $file,
        ])
            ->assertCreated()
            ->assertJsonPath('data.import_job.total_rows', 2)
            ->assertJsonPath('data.import_job.success_rows', 1)
            ->assertJsonPath('data.import_job.failed_rows', 1)
            ->assertJsonPath('data.import_job.status', ImportJob::STATUS_COMPLETED_WITH_ERRORS);

        $this->assertDatabaseHas('employees', [
            'company_id' => $hr->company_id,
            'employee_id' => 'EMP-9001',
            'full_name' => 'Sari Dewi',
        ]);
        $this->assertDatabaseHas('departments', ['company_id' => $hr->company_id, 'name' => 'Finance']);
        $this->assertDatabaseHas('positions', ['company_id' => $hr->company_id, 'name' => 'Staff']);
    }

    public function test_hr_can_import_fingerprint_attendance_from_csv(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $department = Department::factory()->create(['company_id' => $hr->company_id]);
        $position = Position::factory()->for($department)->create(['company_id' => $hr->company_id]);
        $employee = Employee::factory()->for($department)->for($position)->create([
            'company_id' => $hr->company_id,
            'employee_id' => 'EMP-1001',
        ]);
        $file = UploadedFile::fake()->createWithContent('attendance.csv', implode("\n", [
            'employee_id,attendance_date,time_in,time_out,status,source,notes',
            'EMP-1001,2026-05-08,08:02,17:30,present,fingerprint,Imported from device',
        ]));

        Sanctum::actingAs($hr);

        $this->postJson('/api/imports/attendance', [
            'file' => $file,
        ])
            ->assertCreated()
            ->assertJsonPath('data.import_job.success_rows', 1)
            ->assertJsonPath('data.import_job.failed_rows', 0);

        $this->assertDatabaseHas('attendances', [
            'company_id' => $hr->company_id,
            'employee_id' => $employee->id,
            'attendance_date' => '2026-05-08',
            'time_in' => '08:02:00',
            'time_out' => '17:30:00',
            'status' => Attendance::STATUS_PRESENT,
            'attendance_source' => Attendance::SOURCE_FINGERPRINT,
        ]);
    }

    public function test_attendance_import_uses_request_source_and_rejects_invalid_time_order(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $department = Department::factory()->create(['company_id' => $hr->company_id]);
        $position = Position::factory()->for($department)->create(['company_id' => $hr->company_id]);
        $employee = Employee::factory()->for($department)->for($position)->create([
            'company_id' => $hr->company_id,
            'employee_id' => 'EMP-1002',
        ]);
        $file = UploadedFile::fake()->createWithContent('attendance.csv', implode("\n", [
            'employee_id,attendance_date,time_in,time_out,status,notes',
            'EMP-1002,2026-05-08,08:02,17:30,present,Imported from adjusted file',
            'EMP-1002,2026-05-09,17:30,08:02,present,Invalid row',
        ]));

        Sanctum::actingAs($hr);

        $this->postJson('/api/imports/attendance', [
            'file' => $file,
            'source' => Attendance::SOURCE_IMPORT,
        ])
            ->assertCreated()
            ->assertJsonPath('data.import_job.success_rows', 1)
            ->assertJsonPath('data.import_job.failed_rows', 1)
            ->assertJsonPath('data.import_job.summary.source', Attendance::SOURCE_IMPORT);

        $this->assertDatabaseHas('attendances', [
            'company_id' => $hr->company_id,
            'employee_id' => $employee->id,
            'attendance_date' => '2026-05-08',
            'attendance_source' => Attendance::SOURCE_IMPORT,
        ]);
        $this->assertDatabaseMissing('attendances', [
            'company_id' => $hr->company_id,
            'employee_id' => $employee->id,
            'attendance_date' => '2026-05-09',
        ]);
    }

    public function test_exports_return_csv_downloads(): void
    {
        $hr = User::factory()->create(['role' => User::ROLE_HR]);
        $department = Department::factory()->create(['company_id' => $hr->company_id]);
        $position = Position::factory()->for($department)->create(['company_id' => $hr->company_id]);
        $employee = Employee::factory()->for($department)->for($position)->create(['company_id' => $hr->company_id]);
        Payroll::factory()->for($employee)->create([
            'company_id' => $hr->company_id,
            'period_year' => 2026,
            'period_month' => 5,
        ]);
        Attendance::factory()->for($employee)->create([
            'company_id' => $hr->company_id,
            'attendance_date' => '2026-05-08',
            'status' => Attendance::STATUS_PRESENT,
        ]);

        Sanctum::actingAs($hr);

        $this->get('/api/exports/employees')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->get('/api/exports/payroll?period_year=2026&period_month=5')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->get('/api/exports/attendance-recap?year=2026&month=5')
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_employee_role_cannot_use_import_export_endpoints(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => User::ROLE_EMPLOYEE]));

        $this->getJson('/api/import-jobs')->assertForbidden();
        $this->get('/api/exports/employees')->assertForbidden();
    }
}
