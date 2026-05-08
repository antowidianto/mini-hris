<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\UpdateSettingsRequest;
use App\Http\Resources\SettingResource;
use App\Models\AuditLog;
use App\Services\AuditLogService;
use App\Services\SettingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settingService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success('Settings retrieved', [
            'settings' => SettingResource::collection($this->settingService->allForCompany(request()->user()->companyId())),
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $this->settingService->updateForCompany($request->user()->companyId(), $request->validated('settings'));
        $this->auditLogService->record(
            $request->user(),
            AuditLog::ACTION_UPDATED,
            AuditLog::MODULE_SETTINGS,
            'Updated global configuration settings.'
        );

        return ApiResponse::success('Settings updated successfully', [
            'settings' => SettingResource::collection($this->settingService->allForCompany($request->user()->companyId())),
        ]);
    }
}
