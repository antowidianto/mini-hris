<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Attendance\AttendanceImportPlaceholderRequest;
use App\Http\Requests\Attendance\AttendanceReportRequest;
use App\Http\Requests\Attendance\ListMyAttendanceRequest;
use App\Http\Requests\Attendance\MonthlyAttendanceRecapRequest;
use App\Http\Resources\AttendanceResource;
use App\Services\AttendanceService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function __construct(private readonly AttendanceService $attendanceService) {}

    public function clockIn(Request $request): JsonResponse
    {
        $attendance = $this->attendanceService->clockIn($request->user());

        return ApiResponse::success('Clock-in recorded successfully', [
            'attendance' => new AttendanceResource($attendance),
        ], 201);
    }

    public function clockOut(Request $request): JsonResponse
    {
        $attendance = $this->attendanceService->clockOut($request->user());

        return ApiResponse::success('Clock-out recorded successfully', [
            'attendance' => new AttendanceResource($attendance),
        ]);
    }

    public function my(ListMyAttendanceRequest $request): JsonResponse
    {
        $attendances = $this->attendanceService->myAttendance($request->user(), $request->validated());
        $today = $this->attendanceService->todayFor($request->user());
        $payload = AttendanceResource::collection($attendances)->response()->getData(true);

        return ApiResponse::success('Attendance history retrieved', [
            'today' => $today ? new AttendanceResource($today) : null,
            'attendances' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
        ]);
    }

    public function report(AttendanceReportRequest $request): JsonResponse
    {
        $attendances = $this->attendanceService->report($request->validated(), $request->user());
        $payload = AttendanceResource::collection($attendances)->response()->getData(true);

        return ApiResponse::success('Attendance report retrieved', [
            'attendances' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
        ]);
    }

    public function monthlyRecap(MonthlyAttendanceRecapRequest $request): JsonResponse
    {
        return ApiResponse::success('Monthly attendance recap retrieved', [
            'recap' => $this->attendanceService->monthlyRecap($request->validated(), $request->user()),
        ]);
    }

    public function importPlaceholder(AttendanceImportPlaceholderRequest $request): JsonResponse
    {
        return ApiResponse::success('Attendance import placeholder accepted', [
            'import' => $this->attendanceService->importPlaceholder($request->validated(), $request->user()),
        ], 202);
    }
}
