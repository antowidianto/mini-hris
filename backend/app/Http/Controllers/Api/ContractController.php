<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Contracts\ListExpiringContractsRequest;
use App\Http\Requests\Contracts\StoreEmployeeContractRequest;
use App\Http\Resources\ContractExpiryResource;
use App\Http\Resources\EmployeeContractResource;
use App\Models\Employee;
use App\Services\ContractService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ContractController extends Controller
{
    public function __construct(private readonly ContractService $contractService) {}

    public function expiring(ListExpiringContractsRequest $request): JsonResponse
    {
        $employees = $this->contractService->expiringEmployees(
            $request->user(),
            (int) ($request->validated('days') ?? 60),
            (int) ($request->validated('per_page') ?? 10)
        );
        $payload = ContractExpiryResource::collection($employees)->response()->getData(true);

        return ApiResponse::success('Expiring contracts retrieved', [
            'contracts' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
        ]);
    }

    public function history(Employee $employee): JsonResponse
    {
        abort_unless((int) $employee->company_id === request()->user()->companyId(), 404);

        return ApiResponse::success('Employee contract history retrieved', [
            'contracts' => EmployeeContractResource::collection($this->contractService->history($employee)),
        ]);
    }

    public function renew(StoreEmployeeContractRequest $request, Employee $employee): JsonResponse
    {
        abort_unless((int) $employee->company_id === $request->user()->companyId(), 404);

        $contract = $this->contractService->renew($employee, $request->validated(), $request->user());

        return ApiResponse::success('Contract renewal recorded successfully', [
            'contract' => new EmployeeContractResource($contract),
        ], 201);
    }
}
