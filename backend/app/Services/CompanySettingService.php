<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\CompanySetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CompanySettingService
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly SettingService $settingService
    ) {}

    public function get(?User $user = null): CompanySetting
    {
        $companyId = $user?->companyId() ?? 0;
        $settings = CompanySetting::query()->firstOrCreate(
            $companyId > 0 ? ['company_id' => $companyId] : ['id' => 1],
            CompanySetting::defaults()
        );
        $this->settingService->syncFromCompanySettings($settings, overwriteExisting: false);

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(array $data, User $actor): CompanySetting
    {
        return DB::transaction(function () use ($actor, $data) {
            $settings = $this->get($actor);
            $settings->update($data);
            $this->settingService->syncFromCompanySettings($settings->refresh());

            $this->auditLogService->record(
                $actor,
                AuditLog::ACTION_UPDATED,
                AuditLog::MODULE_SETTINGS,
                "Updated company settings for {$settings->company_name}."
            );

            return $settings->refresh();
        });
    }
}
