<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class AuditLogService
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, User $user): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 10), 50);

        return AuditLog::query()
            ->with('user')
            ->where('company_id', $user->companyId())
            ->filter($filters)
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function record(?User $user, string $action, string $module, string $description, ?Request $request = null): AuditLog
    {
        $request ??= request();

        return AuditLog::query()->create([
            'company_id' => $user?->companyId(),
            'user_id' => $user?->id,
            'action' => $action,
            'module' => $module,
            'description' => $description,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }
}
