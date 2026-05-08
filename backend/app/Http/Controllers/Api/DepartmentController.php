<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DepartmentResource;
use App\Models\Department;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $departments = Department::query()
            ->where('company_id', $request->user()->companyId())
            ->orderBy('name')
            ->get();

        return ApiResponse::success('Departments retrieved', [
            'departments' => DepartmentResource::collection($departments),
        ]);
    }
}
