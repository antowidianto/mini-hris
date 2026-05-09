<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
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

    /**
     * @return array<string, list<array<string, mixed>>>
     */
    public function filterOptions(User $user): array
    {
        return [
            'modules' => collect(AuditLog::MODULES)
                ->map(fn (string $value): array => [
                    'value' => $value,
                    'label' => AuditLog::moduleLabels()[$value],
                ])
                ->values()
                ->all(),
            'actions' => collect(AuditLog::ACTIONS)
                ->map(fn (string $value): array => [
                    'value' => $value,
                    'label' => AuditLog::actionLabels()[$value],
                ])
                ->values()
                ->all(),
            'users' => $this->usersWithAuditLogs($user)
                ->map(fn (User $auditUser): array => [
                    'id' => $auditUser->id,
                    'name' => $auditUser->name,
                    'email' => $auditUser->email,
                    'role' => $auditUser->role,
                ])
                ->values()
                ->all(),
        ];
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

    /**
     * @return Collection<int, User>
     */
    private function usersWithAuditLogs(User $user): Collection
    {
        $companyId = $user->companyId();

        return User::query()
            ->where('company_id', $companyId)
            ->whereHas('auditLogs', fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('name')
            ->get();
    }
}
