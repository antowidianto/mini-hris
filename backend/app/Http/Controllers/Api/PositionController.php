<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Positions\ListPositionsRequest;
use App\Http\Resources\PositionResource;
use App\Models\Position;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class PositionController extends Controller
{
    public function index(ListPositionsRequest $request): JsonResponse
    {
        $positions = Position::query()
            ->with('department')
            ->where('company_id', $request->user()->companyId())
            ->when($request->validated('department_id'), fn ($query, $departmentId) => $query->where('department_id', $departmentId))
            ->orderBy('name')
            ->get();

        return ApiResponse::success('Positions retrieved', [
            'positions' => PositionResource::collection($positions),
        ]);
    }
}
