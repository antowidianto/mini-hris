<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\BranchResource;
use App\Models\Branch;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $branches = Branch::query()
            ->where('company_id', $request->user()->companyId())
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return ApiResponse::success('Branches retrieved', [
            'branches' => BranchResource::collection($branches),
        ]);
    }
}
