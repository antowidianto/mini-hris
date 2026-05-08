<?php

namespace App\Services;

use App\Models\PayrollComponent;
use Illuminate\Database\Eloquent\Collection;

class PayrollComponentService
{
    /**
     * @return Collection<int, PayrollComponent>
     */
    public function all(?int $companyId = null): Collection
    {
        $this->ensureDefaults($companyId);

        return PayrollComponent::query()
            ->when($companyId, fn ($query) => $query->where('company_id', $companyId))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PayrollComponent $component, array $data): PayrollComponent
    {
        $component->update($data);

        return $component->refresh();
    }

    public function ensureDefaults(?int $companyId = null): void
    {
        foreach ($this->defaults() as $component) {
            PayrollComponent::query()->firstOrCreate(
                ['company_id' => $companyId, 'code' => $component['code']],
                [...$component, 'company_id' => $companyId]
            );
        }
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function defaults(): array
    {
        return [
            ['code' => 'BASIC_SALARY', 'name' => 'Basic Salary', 'type' => PayrollComponent::TYPE_EARNING, 'is_active' => true, 'sort_order' => 10],
            ['code' => 'FIXED_ALLOWANCE', 'name' => 'Fixed Allowance', 'type' => PayrollComponent::TYPE_EARNING, 'is_active' => true, 'sort_order' => 20],
            ['code' => 'NON_FIXED_ALLOWANCE', 'name' => 'Non-Fixed Allowance', 'type' => PayrollComponent::TYPE_EARNING, 'is_active' => true, 'sort_order' => 30],
            ['code' => 'MEAL_ALLOWANCE', 'name' => 'Meal Allowance', 'type' => PayrollComponent::TYPE_EARNING, 'is_active' => true, 'sort_order' => 40],
            ['code' => 'TRANSPORT_ALLOWANCE', 'name' => 'Transport Allowance', 'type' => PayrollComponent::TYPE_EARNING, 'is_active' => true, 'sort_order' => 50],
            ['code' => 'ATTENDANCE_DEDUCTION', 'name' => 'Attendance Deduction', 'type' => PayrollComponent::TYPE_DEDUCTION, 'is_active' => true, 'sort_order' => 110],
            ['code' => 'LATE_DEDUCTION', 'name' => 'Late Deduction', 'type' => PayrollComponent::TYPE_DEDUCTION, 'is_active' => true, 'sort_order' => 120],
            ['code' => 'BPJS_KESEHATAN_EMPLOYEE', 'name' => 'BPJS Kesehatan Employee', 'type' => PayrollComponent::TYPE_DEDUCTION, 'is_active' => true, 'sort_order' => 130],
            ['code' => 'BPJS_JHT_EMPLOYEE', 'name' => 'BPJS JHT Employee', 'type' => PayrollComponent::TYPE_DEDUCTION, 'is_active' => true, 'sort_order' => 140],
            ['code' => 'BPJS_JP_EMPLOYEE', 'name' => 'BPJS JP Employee', 'type' => PayrollComponent::TYPE_DEDUCTION, 'is_active' => true, 'sort_order' => 150],
            ['code' => 'PPH21', 'name' => 'PPh 21 Placeholder', 'type' => PayrollComponent::TYPE_DEDUCTION, 'is_active' => true, 'sort_order' => 160],
        ];
    }
}
