<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AuditLogs\ListAuditLogsRequest;
use App\Http\Resources\AuditLogResource;
use App\Services\AuditLogService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function index(ListAuditLogsRequest $request): JsonResponse
    {
        $auditLogs = $this->auditLogService->paginate($request->validated(), $request->user());
        $payload = AuditLogResource::collection($auditLogs)->response()->getData(true);

        return ApiResponse::success('Audit logs retrieved', [
            'audit_logs' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
            'filters' => $this->auditLogService->filterOptions($request->user()),
        ]);
    }
}
