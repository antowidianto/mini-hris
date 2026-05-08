<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CompanySettings\UpdateCompanySettingRequest;
use App\Http\Resources\ApprovalFlowResource;
use App\Http\Resources\CompanySettingResource;
use App\Http\Resources\PayrollComponentResource;
use App\Http\Resources\SettingResource;
use App\Services\ApprovalFlowService;
use App\Services\CompanySettingService;
use App\Services\PayrollComponentService;
use App\Services\SettingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class CompanySettingController extends Controller
{
    public function __construct(
        private readonly CompanySettingService $companySettingService,
        private readonly SettingService $settingService,
        private readonly PayrollComponentService $payrollComponentService,
        private readonly ApprovalFlowService $approvalFlowService
    ) {}

    public function show(): JsonResponse
    {
        return ApiResponse::success('Company settings retrieved', [
            'company_settings' => new CompanySettingResource($this->companySettingService->get(request()->user())),
            'settings' => SettingResource::collection($this->settingService->allForCompany(request()->user()->companyId())),
            'payroll_components' => PayrollComponentResource::collection($this->payrollComponentService->all(request()->user()->companyId())),
            'approval_flows' => ApprovalFlowResource::collection($this->approvalFlowService->all(request()->user()->companyId())),
        ]);
    }

    public function update(UpdateCompanySettingRequest $request): JsonResponse
    {
        $settings = $this->companySettingService->update($request->validated(), $request->user());

        return ApiResponse::success('Company settings updated successfully', [
            'company_settings' => new CompanySettingResource($settings),
            'settings' => SettingResource::collection($this->settingService->allForCompany($request->user()->companyId())),
            'payroll_components' => PayrollComponentResource::collection($this->payrollComponentService->all($request->user()->companyId())),
            'approval_flows' => ApprovalFlowResource::collection($this->approvalFlowService->all($request->user()->companyId())),
        ]);
    }
}
