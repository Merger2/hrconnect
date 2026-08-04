<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class SettingsManagementService
{
    public function update(string $key, $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    public function updateValue(int $id, mixed $value): array
    {
        $setting = Setting::find($id);

        if ($setting) {
            $setting->update(['value' => (string) $value]);
            Cache::forget("setting.{$setting->key}");
        }

        return [
            'setting' => $setting,
            'license_state' => $this->enterpriseLicenseState(false, ''),
        ];
    }

    public function groupedSettings(): Collection
    {
        return Setting::query()
            ->orderBy('group')
            ->orderBy('id')
            ->get()
            ->groupBy('group');
    }

    public function hardwareId(): string
    {
        $hwid = Setting::getValue('_hardware_id');

        if (! $hwid) {
            $hwid = strtoupper(Str::random(32));
            Setting::updateOrCreate(['key' => '_hardware_id'], ['value' => $hwid]);
        }

        return $hwid;
    }

    public function enterpriseLicenseState(bool $reloadDraft = true, string $currentDraft = ''): array
    {
        $setting = Setting::query()->where('key', 'enterprise_license')->first();

        $draft = $reloadDraft ? ($setting->value ?? '') : $currentDraft;
        $valid = ! empty($draft) && strlen($draft) >= 32;

        return [
            'setting_id' => $setting?->id,
            'draft' => $draft,
            'validation' => [
                'valid' => $valid,
                'license' => $valid ? ['key' => $draft, 'type' => 'enterprise'] : null,
                'message' => $valid ? 'Enterprise license validated.' : 'No valid enterprise license.',
            ],
        ];
    }

    public function applyEnterpriseLicense(string $draft): array
    {
        $valid = strlen($draft) >= 32;

        if ($valid) {
            Setting::updateOrCreate(['key' => 'enterprise_license'], ['value' => $draft]);
        }

        $state = $this->enterpriseLicenseState(false, $draft);

        return [
            'setting' => $valid ? Setting::query()->where('key', 'enterprise_license')->first() : null,
            'license_state' => $state,
        ];
    }
}
