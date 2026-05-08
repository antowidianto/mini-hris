<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function show(Request $request): JsonResponse
    {
        return ApiResponse::success('Dashboard metrics retrieved', $this->dashboardService->forUser($request->user()));
    }
}
