<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ContractService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    public function expiringEmployees(User $actor, int $days = 60, int $perPage = 10): LengthAwarePaginator
    {
        return $this->expiringQuery($actor->companyId(), $days)
            ->orderBy('contract_end_date')
            ->paginate(min($perPage, 50))
            ->withQueryString();
    }

    /**
     * @return Collection<int, Employee>
     */
    public function expiringPreview(User $actor, int $days = 60, int $limit = 5): Collection
    {
        return $this->expiringQuery($actor->companyId(), $days)
            ->orderBy('contract_end_date')
            ->limit($limit)
            ->get();
    }

    /**
     * @return array<string, int>
     */
    public function expiringCounts(User $actor): array
    {
        return [
            'contracts_expiring_30_days' => $this->expiringQuery($actor->companyId(), 30)->count(),
            'contracts_expiring_60_days' => $this->expiringQuery($actor->companyId(), 60)->count(),
        ];
    }

    /**
     * @return Collection<int, EmployeeContract>
     */
    public function history(Employee $employee): Collection
    {
        return $employee->contracts()
            ->with('creator')
            ->latest('contract_start_date')
            ->latest()
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function renew(Employee $employee, array $data, User $actor): EmployeeContract
    {
        return DB::transaction(function () use ($actor, $data, $employee) {
            if ((int) $employee->company_id !== $actor->companyId()) {
                abort(404);
            }

            $contract = EmployeeContract::query()->create([
                ...$data,
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'renewal_date' => $data['renewal_date'] ?? now()->toDateString(),
                'created_by' => $actor->id,
            ]);

            $employee->update([
                'employment_type' => $contract->employment_type,
                'contract_start_date' => $contract->contract_start_date?->format('Y-m-d'),
                'contract_end_date' => $contract->contract_end_date?->format('Y-m-d'),
            ]);

            $this->auditLogService->record(
                $actor,
                AuditLog::ACTION_CREATED,
                AuditLog::MODULE_CONTRACT,
                "Recorded contract renewal for {$employee->employee_id} - {$employee->full_name}."
            );

            return $contract->load('creator');
        });
    }

    private function expiringQuery(int $companyId, int $days): Builder
    {
        return Employee::query()
            ->with(['branch', 'department', 'position', 'supervisor.department', 'supervisor.position'])
            ->where('company_id', $companyId)
            ->where('employment_status', Employee::STATUS_ACTIVE)
            ->whereIn('employment_type', [Employee::EMPLOYMENT_TYPE_PROBATION, Employee::EMPLOYMENT_TYPE_PKWT])
            ->whereNotNull('contract_end_date')
            ->whereDate('contract_end_date', '>=', now()->toDateString())
            ->whereDate('contract_end_date', '<=', now()->addDays($days)->toDateString());
    }
}
