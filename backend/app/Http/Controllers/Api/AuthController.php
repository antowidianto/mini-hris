<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterTenantRequest;
use App\Http\Resources\AuthUserResource;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\TenantOnboardingService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly TenantOnboardingService $tenantOnboardingService,
    ) {}

    public function register(RegisterTenantRequest $request): JsonResponse
    {
        $result = $this->tenantOnboardingService->register($request->validated());
        /** @var User $user */
        $user = $result['user'];
        $token = $user->createToken('mini-hris-spa')->plainTextToken;

        $this->auditLogService->record(
            $user,
            AuditLog::ACTION_CREATED,
            AuditLog::MODULE_AUTH,
            "Tenant workspace {$user->company?->name} was registered.",
            $request
        );

        return ApiResponse::success('Workspace registered successfully', [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new AuthUserResource($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        /** @var User|null $user */
        $user = User::query()->where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return ApiResponse::error('Invalid email or password', null, 422);
        }

        if (! $user->company?->hasActiveSubscription()) {
            return ApiResponse::error('Your workspace subscription is inactive. Please update billing to continue.', null, 402);
        }

        $token = $user->createToken('mini-hris-spa')->plainTextToken;
        $this->auditLogService->record(
            $user,
            AuditLog::ACTION_LOGIN,
            AuditLog::MODULE_AUTH,
            "User {$user->email} logged in.",
            $request
        );

        return ApiResponse::success('Login successful', [
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new AuthUserResource($user),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return ApiResponse::success('Authenticated user retrieved', [
            'user' => new AuthUserResource($request->user()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        $accessToken = $user?->currentAccessToken();

        if ($user) {
            $this->auditLogService->record(
                $user,
                AuditLog::ACTION_LOGOUT,
                AuditLog::MODULE_AUTH,
                "User {$user->email} logged out.",
                $request
            );
        }

        if ($accessToken instanceof PersonalAccessToken) {
            $accessToken->delete();
        } else {
            $user?->tokens()->delete();
        }

        return ApiResponse::success('Logout successful');
    }
}
