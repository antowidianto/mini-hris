<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EmployeeService
{
    public function __construct(private readonly AuditLogService $auditLogService) {}

    /**
     * @param  array<string, mixed>  $filters
     */
    public function paginate(array $filters, User $actor): LengthAwarePaginator
    {
        $perPage = min((int) ($filters['per_page'] ?? 10), 50);

        return Employee::query()
            ->forCompany($actor->companyId())
            ->with(['branch', 'department', 'position', 'supervisor.department', 'supervisor.position', 'user'])
            ->search($filters['search'] ?? null)
            ->when($filters['branch_id'] ?? null, fn ($query, $branchId) => $query->where('branch_id', $branchId))
            ->when($filters['department_id'] ?? null, fn ($query, $departmentId) => $query->where('department_id', $departmentId))
            ->when($filters['position_id'] ?? null, fn ($query, $positionId) => $query->where('position_id', $positionId))
            ->when($filters['employment_status'] ?? null, fn ($query, $status) => $query->where('employment_status', $status))
            ->when($filters['employment_type'] ?? null, fn ($query, $type) => $query->where('employment_type', $type))
            ->latest()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Employee
    {
        return DB::transaction(function () use ($actor, $data) {
            $data['company_id'] = $actor->companyId();
            $this->ensureReferencesBelongToCompany($data, $actor->companyId());

            $employee = Employee::query()->create($data);
            $this->auditLogService->record(
                $actor,
                AuditLog::ACTION_CREATED,
                AuditLog::MODULE_EMPLOYEE,
                "Created employee {$employee->employee_id} - {$employee->full_name}."
            );

            return $employee->load(['branch', 'department', 'position', 'supervisor.department', 'supervisor.position', 'user']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Employee $employee, array $data, User $actor): Employee
    {
        return DB::transaction(function () use ($actor, $employee, $data) {
            $this->ensureEmployeeBelongsToCompany($employee, $actor);
            $this->ensureReferencesBelongToCompany($data, $actor->companyId());
            $employee->update($data);
            $this->auditLogService->record(
                $actor,
                AuditLog::ACTION_UPDATED,
                AuditLog::MODULE_EMPLOYEE,
                "Updated employee {$employee->employee_id} - {$employee->full_name}."
            );

            return $employee->refresh()->load(['branch', 'department', 'position', 'supervisor.department', 'supervisor.position', 'user']);
        });
    }

    public function deactivate(Employee $employee, User $actor): Employee
    {
        return DB::transaction(function () use ($actor, $employee) {
            $this->ensureEmployeeBelongsToCompany($employee, $actor);
            $employee->update(['employment_status' => Employee::STATUS_INACTIVE]);
            $this->auditLogService->record(
                $actor,
                AuditLog::ACTION_DEACTIVATED,
                AuditLog::MODULE_EMPLOYEE,
                "Deactivated employee {$employee->employee_id} - {$employee->full_name}."
            );

            return $employee->refresh()->load(['branch', 'department', 'position', 'supervisor.department', 'supervisor.position', 'user']);
        });
    }

    /**
     * @return Collection<int, Employee>
     */
    public function activeEmployees(User $actor): Collection
    {
        return Employee::query()
            ->forCompany($actor->companyId())
            ->with(['branch', 'department', 'position'])
            ->where('employment_status', Employee::STATUS_ACTIVE)
            ->orderBy('full_name')
            ->get();
    }

    public function ensureEmployeeBelongsToCompany(Employee $employee, User $actor): void
    {
        if ((int) $employee->company_id !== $actor->companyId()) {
            abort(404);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function ensureReferencesBelongToCompany(array $data, int $companyId): void
    {
        $checks = [
            'branch_id' => Branch::class,
            'department_id' => Department::class,
            'position_id' => Position::class,
            'supervisor_id' => Employee::class,
            'user_id' => User::class,
        ];

        foreach ($checks as $key => $model) {
            if (! isset($data[$key])) {
                continue;
            }

            $exists = $model::query()
                ->whereKey($data[$key])
                ->where('company_id', $companyId)
                ->exists();

            if (! $exists) {
                throw ValidationException::withMessages([
                    $key => ['The selected value is invalid for your company.'],
                ]);
            }
        }
    }
}
