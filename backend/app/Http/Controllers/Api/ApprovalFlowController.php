<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ReplaceApprovalFlowsRequest;
use App\Http\Resources\ApprovalFlowResource;
use App\Models\AuditLog;
use App\Services\ApprovalFlowService;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ApprovalFlowController extends Controller
{
    public function __construct(
        private readonly ApprovalFlowService $approvalFlowService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function index(): JsonResponse
    {
        return ApiResponse::success('Approval flows retrieved', [
            'approval_flows' => ApprovalFlowResource::collection($this->approvalFlowService->all(request()->user()->companyId())),
        ]);
    }

    public function replace(ReplaceApprovalFlowsRequest $request): JsonResponse
    {
        $flows = $this->approvalFlowService->replace($request->validated('flows'), $request->user()->companyId());
        $this->auditLogService->record(
            $request->user(),
            AuditLog::ACTION_UPDATED,
            AuditLog::MODULE_SETTINGS,
            'Updated approval flow configuration.'
        );

        return ApiResponse::success('Approval flows updated successfully', [
            'approval_flows' => ApprovalFlowResource::collection($flows),
        ]);
    }
}
