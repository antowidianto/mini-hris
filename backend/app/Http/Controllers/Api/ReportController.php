<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Reports\OperationalReportRequest;
use App\Services\ReportingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(private readonly ReportingService $reportingService) {}

    public function operational(OperationalReportRequest $request): JsonResponse
    {
        return ApiResponse::success('Operational reports retrieved', [
            'report' => $this->reportingService->operational($request->validated(), $request->user()),
        ]);
    }
}
