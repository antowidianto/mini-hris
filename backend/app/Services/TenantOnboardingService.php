<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Company;
use App\Models\CompanySetting;
use App\Models\Department;
use App\Models\Position;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TenantOnboardingService
{
    private const PLAN_LIMITS = [
        'starter' => 25,
        'growth' => 100,
        'scale' => 500,
    ];

    public function __construct(
        private readonly ApprovalFlowService $approvalFlowService,
        private readonly PayrollComponentService $payrollComponentService,
        private readonly SettingService $settingService,
    ) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array{company: Company, user: User}
     */
    public function register(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $plan = $payload['plan'] ?? 'starter';
            $company = Company::query()->create([
                'name' => $payload['company_name'],
                'code' => Str::upper($payload['company_code']),
                'slug' => $this->uniqueSlug($payload['company_name']),
                'address' => $payload['address'] ?? null,
                'plan' => $plan,
                'subscription_status' => Company::SUBSCRIPTION_TRIALING,
                'trial_ends_at' => now()->addDays(14),
                'billing_email' => $payload['billing_email'],
                'employee_limit' => self::PLAN_LIMITS[$plan],
                'is_active' => true,
            ]);

            $companySettings = CompanySetting::query()->create([
                ...CompanySetting::defaults(),
                'company_id' => $company->id,
                'company_name' => $company->name,
                'address' => $company->address,
            ]);

            $this->settingService->syncFromCompanySettings($companySettings);
            $this->payrollComponentService->ensureDefaults($company->id);
            $this->approvalFlowService->ensureDefaults($company->id);
            $this->createStarterOrganization($company);

            $user = User::query()->create([
                'company_id' => $company->id,
                'name' => $payload['admin_name'],
                'email' => $payload['billing_email'],
                'role' => User::ROLE_ADMIN,
                'password' => $payload['password'],
            ]);

            return ['company' => $company, 'user' => $user];
        });
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $counter = 2;

        while (Company::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$counter;
            $counter++;
        }

        return $slug;
    }

    private function createStarterOrganization(Company $company): void
    {
        Branch::query()->create([
            'company_id' => $company->id,
            'name' => 'Head Office',
            'code' => 'HO',
            'type' => Branch::TYPE_BRANCH,
            'area' => 'Main',
            'address' => $company->address,
            'is_active' => true,
        ]);

        $department = Department::query()->create([
            'company_id' => $company->id,
            'name' => 'Human Resources',
            'description' => 'Default HR department for SaaS onboarding.',
        ]);

        Position::query()->create([
            'company_id' => $company->id,
            'department_id' => $department->id,
            'name' => 'HR Administrator',
            'description' => 'Initial workspace administrator role.',
        ]);
    }
}

