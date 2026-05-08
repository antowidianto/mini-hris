<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ImportExport\ExportAttendanceRecapRequest;
use App\Http\Requests\ImportExport\ExportEmployeesRequest;
use App\Http\Requests\ImportExport\ExportPayrollRequest;
use App\Http\Requests\ImportExport\ImportCsvRequest;
use App\Http\Requests\ImportExport\ListImportJobsRequest;
use App\Http\Resources\ImportJobResource;
use App\Models\ImportJob;
use App\Services\AttendanceService;
use App\Services\ImportExportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ImportExportController extends Controller
{
    public function __construct(
        private readonly ImportExportService $importExportService,
        private readonly AttendanceService $attendanceService
    ) {}

    public function importJobs(ListImportJobsRequest $request): JsonResponse
    {
        $jobs = ImportJob::query()
            ->forCompany($request->user()->companyId())
            ->when($request->validated('type'), fn ($query, $type) => $query->where('type', $type))
            ->latest()
            ->paginate(min((int) ($request->validated('per_page') ?? 10), 50))
            ->withQueryString();
        $payload = ImportJobResource::collection($jobs)->response()->getData(true);

        return ApiResponse::success('Import jobs retrieved', [
            'import_jobs' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
        ]);
    }

    public function importEmployees(ImportCsvRequest $request): JsonResponse
    {
        $job = $this->importExportService->importEmployees($request->file('file'), $request->user(), $request->validated());

        return ApiResponse::success('Employee import completed', [
            'import_job' => new ImportJobResource($job),
        ], 201);
    }

    public function importAttendance(ImportCsvRequest $request): JsonResponse
    {
        $job = $this->importExportService->importAttendance($request->file('file'), $request->user(), $request->validated());

        return ApiResponse::success('Attendance import completed', [
            'import_job' => new ImportJobResource($job),
        ], 201);
    }

    public function exportEmployees(ExportEmployeesRequest $request): StreamedResponse
    {
        return $this->importExportService->exportEmployees($request->user(), $request->validated());
    }

    public function exportPayroll(ExportPayrollRequest $request): StreamedResponse
    {
        return $this->importExportService->exportPayroll($request->user(), $request->validated());
    }

    public function exportAttendanceRecap(ExportAttendanceRecapRequest $request): StreamedResponse
    {
        $filters = $request->validated();
        $recap = $this->attendanceService->monthlyRecap($filters, $request->user());

        return $this->importExportService->exportAttendanceRecap($recap, (int) $filters['year'], (int) $filters['month']);
    }
}
