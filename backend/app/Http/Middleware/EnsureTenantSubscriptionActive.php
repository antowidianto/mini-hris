<?php

namespace App\Http\Middleware;

use App\Models\Company;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantSubscriptionActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $company = $request->user()?->company;

        if (! $company instanceof Company) {
            return ApiResponse::error('No tenant workspace is assigned to this account.', null, Response::HTTP_FORBIDDEN);
        }

        if (! $company->hasActiveSubscription()) {
            return ApiResponse::error(
                'Your workspace subscription is inactive. Please update billing to continue.',
                ['subscription_status' => $company->subscription_status],
                Response::HTTP_PAYMENT_REQUIRED
            );
        }

        return $next($request);
    }
}
