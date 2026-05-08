<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdatePayrollComponentRequest;
use App\Http\Resources\PayrollComponentResource;
use App\Models\AuditLog;
use App\Models\PayrollComponent;
use App\Services\AuditLogService;
use App\Services\PayrollComponentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PayrollComponentController extends Controller
{
    public function __construct(
        private readonly PayrollComponentService $payrollComponentService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success('Payroll components retrieved', [
            'payroll_components' => PayrollComponentResource::collection($this->payrollComponentService->all(request()->user()->companyId())),
        ]);
    }

    public function update(UpdatePayrollComponentRequest $request, PayrollComponent $payrollComponent): JsonResponse
    {
        abort_unless((int) $payrollComponent->company_id === $request->user()->companyId(), 404);

        $payrollComponent = $this->payrollComponentService->update($payrollComponent, $request->validated());
        $this->auditLogService->record(
            $request->user(),
            AuditLog::ACTION_UPDATED,
            AuditLog::MODULE_SETTINGS,
            "Updated payroll component {$payrollComponent->code}."
        );

        return ApiResponse::success('Payroll component updated successfully', [
            'payroll_component' => new PayrollComponentResource($payrollComponent),
        ]);
    }
}
