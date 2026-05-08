<?php

namespace App\Services;

use App\Models\ApprovalFlow;
use App\Models\ApprovalStep;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ApprovalFlowService
{
    /**
     * @return Collection<int, ApprovalFlow>
     */
    public function all(?int $companyId = null): Collection
    {
        $this->ensureDefaults($companyId);

        return ApprovalFlow::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('module')
            ->orderBy('step_order')
            ->get();
    }

    /**
     * @return Collection<int, ApprovalFlow>
     */
    public function activeSteps(int $companyId, string $module): Collection
    {
        $this->ensureDefaults($companyId);

        return ApprovalFlow::query()
            ->where('company_id', $companyId)
            ->where('module', $module)
            ->where('is_active', true)
            ->orderBy('step_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * @return Collection<int, ApprovalStep>
     */
    public function createRuntimeSteps(Model $approvable, string $module, int $companyId, ?Employee $requester = null): Collection
    {
        $steps = $this->activeSteps($companyId, $module);

        if ($steps->isEmpty()) {
            return new Collection;
        }

        $runtimeSteps = $steps->map(function (ApprovalFlow $flow) use ($approvable, $companyId, $module, $requester) {
            $status = ApprovalStep::STATUS_PENDING;

            if ($flow->role === 'supervisor' && ! $requester?->supervisor_id) {
                $status = ApprovalStep::STATUS_APPROVED;
            }

            return ApprovalStep::query()->create([
                'company_id' => $companyId,
                'module' => $module,
                'approvable_type' => $approvable::class,
                'approvable_id' => $approvable->getKey(),
                'step_order' => $flow->step_order,
                'role' => $flow->role,
                'status' => $status,
            ]);
        });

        return new Collection($runtimeSteps->all());
    }

    public function currentPendingStep(Model $approvable): ?ApprovalStep
    {
        return ApprovalStep::query()
            ->where('approvable_type', $approvable::class)
            ->where('approvable_id', $approvable->getKey())
            ->where('status', ApprovalStep::STATUS_PENDING)
            ->orderBy('step_order')
            ->lockForUpdate()
            ->first();
    }

    public function decideCurrentStep(Model $approvable, User $actor, string $status, ?string $notes = null): ApprovalStep
    {
        $step = $this->currentPendingStep($approvable);

        if (! $step) {
            throw ValidationException::withMessages([
                'approval' => ['There is no pending approval step for this request.'],
            ]);
        }

        if (! $this->actorCanApproveRole($actor, $step->role)) {
            throw ValidationException::withMessages([
                'approval' => ['You are not assigned to the current approval step.'],
            ]);
        }

        return $this->decideStep($step, $actor, $status, $notes);
    }

    public function decideStep(ApprovalStep $step, User $actor, string $status, ?string $notes = null): ApprovalStep
    {
        $step->update([
            'status' => $status,
            'notes' => $notes,
            'approved_by' => $actor->id,
            'approved_at' => now(),
        ]);

        return $step->refresh()->load('approver');
    }

    public function hasPendingSteps(Model $approvable): bool
    {
        return ApprovalStep::query()
            ->where('approvable_type', $approvable::class)
            ->where('approvable_id', $approvable->getKey())
            ->where('status', ApprovalStep::STATUS_PENDING)
            ->exists();
    }

    public function actorCanApproveRole(User $actor, string $role): bool
    {
        if ($role === 'supervisor') {
            return false;
        }

        if ($actor->role === User::ROLE_ADMIN) {
            return in_array($role, [User::ROLE_ADMIN, User::ROLE_HR], true);
        }

        return $actor->role === $role;
    }

    /**
     * @param  list<array<string, mixed>>  $flows
     */
    public function replace(array $flows, ?int $companyId = null): Collection
    {
        $this->ensureDefaults($companyId);

        DB::transaction(function () use ($companyId, $flows) {
            ApprovalFlow::query()
                ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
                ->update(['is_active' => false]);

            foreach ($flows as $flow) {
                ApprovalFlow::query()->updateOrCreate(
                    [
                        'company_id' => $companyId,
                        'module' => $flow['module'],
                        'step_order' => $flow['step_order'],
                        'role' => $flow['role'],
                    ],
                    [
                        'is_active' => $flow['is_active'] ?? true,
                    ]
                );
            }
        });

        return $this->all($companyId);
    }

    public function ensureDefaults(?int $companyId = null): void
    {
        $hasFlows = ApprovalFlow::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->when(! $companyId, fn ($query) => $query->whereNull('company_id'))
            ->exists();

        if ($hasFlows) {
            return;
        }

        foreach ($this->defaults() as $flow) {
            ApprovalFlow::query()->firstOrCreate(
                [
                    'company_id' => $companyId,
                    'module' => $flow['module'],
                    'step_order' => $flow['step_order'],
                    'role' => $flow['role'],
                ],
                [...$flow, 'company_id' => $companyId]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaults(): array
    {
        return [
            ['module' => ApprovalFlow::MODULE_LEAVE, 'step_order' => 1, 'role' => 'supervisor', 'is_active' => true],
            ['module' => ApprovalFlow::MODULE_LEAVE, 'step_order' => 2, 'role' => User::ROLE_HR, 'is_active' => true],
            ['module' => ApprovalFlow::MODULE_PAYROLL, 'step_order' => 1, 'role' => User::ROLE_HR, 'is_active' => true],
            ['module' => ApprovalFlow::MODULE_REQUEST, 'step_order' => 1, 'role' => User::ROLE_HR, 'is_active' => true],
        ];
    }
}
