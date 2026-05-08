<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Leaves\LeaveDecisionRequest;
use App\Http\Requests\Leaves\ListLeaveApprovalsRequest;
use App\Http\Requests\Leaves\ListLeaveRequestsRequest;
use App\Http\Requests\Leaves\StoreLeaveRequestRequest;
use App\Http\Resources\LeaveBalanceResource;
use App\Http\Resources\LeaveRequestResource;
use App\Http\Resources\LeaveTypeResource;
use App\Models\LeaveRequest;
use App\Services\LeaveService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function __construct(private readonly LeaveService $leaveService) {}

    public function types(Request $request): JsonResponse
    {
        return ApiResponse::success('Leave types retrieved', [
            'leave_types' => LeaveTypeResource::collection($this->leaveService->activeTypes($request->user())),
        ]);
    }

    public function balances(Request $request): JsonResponse
    {
        return ApiResponse::success('Leave balances retrieved', [
            'leave_balances' => LeaveBalanceResource::collection($this->leaveService->balancesFor($request->user())),
        ]);
    }

    public function index(ListLeaveRequestsRequest $request): JsonResponse
    {
        $leaveRequests = $this->leaveService->employeeRequests($request->user(), $request->validated());
        $payload = LeaveRequestResource::collection($leaveRequests)->response()->getData(true);

        return ApiResponse::success('Leave requests retrieved', [
            'leave_requests' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
        ]);
    }

    public function store(StoreLeaveRequestRequest $request): JsonResponse
    {
        $leaveRequest = $this->leaveService->submit($request->user(), $request->validated());

        return ApiResponse::success('Leave request submitted successfully', [
            'leave_request' => new LeaveRequestResource($leaveRequest),
        ], 201);
    }

    public function approvals(ListLeaveApprovalsRequest $request): JsonResponse
    {
        $leaveRequests = $this->leaveService->approvalRequests($request->user(), $request->validated());
        $payload = LeaveRequestResource::collection($leaveRequests)->response()->getData(true);

        return ApiResponse::success('Leave approvals retrieved', [
            'leave_requests' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
        ]);
    }

    public function supervisorApprovals(ListLeaveApprovalsRequest $request): JsonResponse
    {
        $leaveRequests = $this->leaveService->supervisorApprovalRequests($request->user(), $request->validated());
        $payload = LeaveRequestResource::collection($leaveRequests)->response()->getData(true);

        return ApiResponse::success('Supervisor leave approvals retrieved', [
            'leave_requests' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
        ]);
    }

    public function supervisorApprove(LeaveDecisionRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest = $this->leaveService->supervisorApprove(
            $leaveRequest,
            $request->user(),
            $request->validated('approval_notes')
        );

        return ApiResponse::success('Leave request approved by supervisor successfully', [
            'leave_request' => new LeaveRequestResource($leaveRequest),
        ]);
    }

    public function supervisorReject(LeaveDecisionRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest = $this->leaveService->supervisorReject(
            $leaveRequest,
            $request->user(),
            $request->validated('approval_notes')
        );

        return ApiResponse::success('Leave request rejected by supervisor successfully', [
            'leave_request' => new LeaveRequestResource($leaveRequest),
        ]);
    }

    public function approve(LeaveDecisionRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest = $this->leaveService->approve(
            $leaveRequest,
            $request->user(),
            $request->validated('approval_notes')
        );

        return ApiResponse::success('Leave request approved successfully', [
            'leave_request' => new LeaveRequestResource($leaveRequest),
        ]);
    }

    public function reject(LeaveDecisionRequest $request, LeaveRequest $leaveRequest): JsonResponse
    {
        $leaveRequest = $this->leaveService->reject(
            $leaveRequest,
            $request->user(),
            $request->validated('approval_notes')
        );

        return ApiResponse::success('Leave request rejected successfully', [
            'leave_request' => new LeaveRequestResource($leaveRequest),
        ]);
    }
}
