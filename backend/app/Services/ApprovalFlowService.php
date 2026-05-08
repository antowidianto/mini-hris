<?php

namespace App\Services;

use App\Models\ApprovalFlow;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

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
