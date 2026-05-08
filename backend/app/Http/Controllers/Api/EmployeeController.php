<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employees\ListEmployeesRequest;
use App\Http\Requests\Employees\StoreEmployeeRequest;
use App\Http\Requests\Employees\UpdateEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Http\Resources\EmployeeSupervisorResource;
use App\Models\Employee;
use App\Services\EmployeeService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function __construct(private readonly EmployeeService $employeeService) {}

    public function index(ListEmployeesRequest $request): JsonResponse
    {
        $employees = $this->employeeService->paginate($request->validated(), $request->user());
        $payload = EmployeeResource::collection($employees)->response()->getData(true);

        return ApiResponse::success('Employees retrieved', [
            'employees' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
        ]);
    }

    public function supervisors(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'exclude_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);

        $supervisors = Employee::query()
            ->with(['department', 'position'])
            ->forCompany($request->user()->companyId())
            ->where('employment_status', Employee::STATUS_ACTIVE)
            ->when($validated['exclude_id'] ?? null, fn ($query, $employeeId) => $query->whereKeyNot($employeeId))
            ->orderBy('full_name')
            ->get();

        return ApiResponse::success('Supervisors retrieved', [
            'supervisors' => EmployeeSupervisorResource::collection($supervisors),
        ]);
    }

    public function store(StoreEmployeeRequest $request): JsonResponse
    {
        $employee = $this->employeeService->create($request->validated(), $request->user());

        return ApiResponse::success('Employee created successfully', [
            'employee' => new EmployeeResource($employee),
        ], 201);
    }

    public function show(Employee $employee): JsonResponse
    {
        $this->employeeService->ensureEmployeeBelongsToCompany($employee, request()->user());
        $employee->load(['branch', 'department', 'position', 'supervisor.department', 'supervisor.position', 'user']);

        return ApiResponse::success('Employee retrieved', [
            'employee' => new EmployeeResource($employee),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): JsonResponse
    {
        $employee = $this->employeeService->update($employee, $request->validated(), $request->user());

        return ApiResponse::success('Employee updated successfully', [
            'employee' => new EmployeeResource($employee),
        ]);
    }

    public function destroy(Employee $employee): JsonResponse
    {
        $employee = $this->employeeService->deactivate($employee, request()->user());

        return ApiResponse::success('Employee deactivated successfully', [
            'employee' => new EmployeeResource($employee),
        ]);
    }
}
