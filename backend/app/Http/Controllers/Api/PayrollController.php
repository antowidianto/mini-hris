<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Payroll\GeneratePayrollRequest;
use App\Http\Requests\Payroll\ListPayrollRequest;
use App\Http\Requests\Payroll\ListPayslipsRequest;
use App\Http\Resources\PayrollResource;
use App\Models\Payroll;
use App\Services\PayrollService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PayrollController extends Controller
{
    public function __construct(private readonly PayrollService $payrollService) {}

    public function index(ListPayrollRequest $request): JsonResponse
    {
        $payrolls = $this->payrollService->paginate($request->validated(), $request->user());
        $payload = PayrollResource::collection($payrolls)->response()->getData(true);

        return ApiResponse::success('Payroll records retrieved', [
            'payrolls' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
        ]);
    }

    public function generate(GeneratePayrollRequest $request): JsonResponse
    {
        $payrolls = $this->payrollService->generate($request->validated(), $request->user());

        return ApiResponse::success('Payroll generated successfully', [
            'payrolls' => PayrollResource::collection($payrolls),
        ], 201);
    }

    public function show(Payroll $payroll): JsonResponse
    {
        abort_unless((int) $payroll->company_id === request()->user()->companyId(), 404);
        $payroll->load(['employee.department', 'employee.position', 'generator']);

        return ApiResponse::success('Payroll record retrieved', [
            'payroll' => new PayrollResource($payroll),
        ]);
    }

    public function payslips(ListPayslipsRequest $request): JsonResponse
    {
        $payrolls = $this->payrollService->payslips($request->user(), $request->validated());
        $payload = PayrollResource::collection($payrolls)->response()->getData(true);

        return ApiResponse::success('Payslips retrieved', [
            'payrolls' => $payload['data'],
            'links' => $payload['links'],
            'meta' => $payload['meta'],
        ]);
    }

    public function payslip(ListPayslipsRequest $request, Payroll $payroll): JsonResponse
    {
        $payroll = $this->payrollService->payslip($request->user(), $payroll);

        return ApiResponse::success('Payslip retrieved', [
            'payroll' => new PayrollResource($payroll),
        ]);
    }
}
