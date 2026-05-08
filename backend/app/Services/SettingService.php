<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Collection;

class SettingService
{
    /**
     * @return Collection<int, Setting>
     */
    public function allGlobal(): Collection
    {
        $this->ensureDefaults();

        return Setting::query()
            ->global()
            ->orderBy('key')
            ->get();
    }

    /**
     * @return Collection<int, Setting>
     */
    public function allForCompany(int $companyId): Collection
    {
        $this->ensureDefaults($companyId);

        return Setting::query()
            ->where('scope', Setting::SCOPE_COMPANY)
            ->where('scope_id', $companyId)
            ->orderBy('key')
            ->get();
    }

    public function value(string $key, mixed $default = null): mixed
    {
        $this->ensureDefaults();

        $setting = Setting::query()
            ->global()
            ->where('key', $key)
            ->first();

        return $setting?->typedValue() ?? $default;
    }

    public function valueForCompany(int $companyId, string $key, mixed $default = null): mixed
    {
        $this->ensureDefaults($companyId);

        $setting = Setting::query()
            ->where('scope', Setting::SCOPE_COMPANY)
            ->where('scope_id', $companyId)
            ->where('key', $key)
            ->first();

        return $setting?->typedValue() ?? $default;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function updateGlobal(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->setGlobal($key, $value);
        }

        $this->syncKnownKeysToCompanySettings(0, $settings);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function updateForCompany(int $companyId, array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->setCompany($companyId, $key, $value);
        }

        $this->syncKnownKeysToCompanySettings($companyId, $settings);
    }

    public function setGlobal(string $key, mixed $value): Setting
    {
        return Setting::query()->updateOrCreate(
            [
                'key' => $key,
                'scope' => Setting::SCOPE_GLOBAL,
                'scope_id' => 0,
            ],
            [
                'value' => $this->encodeValue($value),
            ]
        );
    }

    public function setCompany(int $companyId, string $key, mixed $value): Setting
    {
        return Setting::query()->updateOrCreate(
            [
                'key' => $key,
                'scope' => Setting::SCOPE_COMPANY,
                'scope_id' => $companyId,
            ],
            [
                'value' => $this->encodeValue($value),
            ]
        );
    }

    public function ensureDefaults(?int $companyId = null): void
    {
        $companyId ??= 0;
        $query = CompanySetting::query();

        if ($companyId > 0) {
            $query->where('company_id', $companyId);
        }

        $this->syncFromCompanySettings(
            $query->firstOrCreate($companyId > 0 ? ['company_id' => $companyId] : ['id' => 1], CompanySetting::defaults()),
            overwriteExisting: false
        );
    }

    public function syncFromCompanySettings(CompanySetting $companySetting, bool $overwriteExisting = true): void
    {
        foreach ($this->companySettingPayload($companySetting) as $key => $value) {
            $scope = $companySetting->company_id ? Setting::SCOPE_COMPANY : Setting::SCOPE_GLOBAL;
            $scopeId = $companySetting->company_id ?: 0;

            if (! $overwriteExisting && Setting::query()->where('scope', $scope)->where('scope_id', $scopeId)->where('key', $key)->exists()) {
                continue;
            }

            if ($companySetting->company_id) {
                $this->setCompany($companySetting->company_id, $key, $value);
            } else {
                $this->setGlobal($key, $value);
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function companySettingPayload(CompanySetting $companySetting): array
    {
        return collect(CompanySetting::defaults())
            ->keys()
            ->mapWithKeys(fn (string $key) => [$key => $companySetting->{$key}])
            ->all();
    }

    private function encodeValue(mixed $value): ?string
    {
        return $value === null ? null : json_encode($value);
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function syncKnownKeysToCompanySettings(int $companyId, array $settings): void
    {
        $knownKeys = collect(CompanySetting::defaults())->keys();
        $payload = collect($settings)
            ->only($knownKeys)
            ->all();

        if ($payload === []) {
            return;
        }

        CompanySetting::query()
            ->firstOrCreate($companyId > 0 ? ['company_id' => $companyId] : ['id' => 1], CompanySetting::defaults())
            ->update($payload);
    }
}
