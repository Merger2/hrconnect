<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\Setting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class SettingsManagementService
{
    public function update(string $key, $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    public function updateValue(int $id, mixed $value): ?Setting
    {
        $setting = Setting::find($id);

        if ($setting) {
            $setting->update(['value' => (string) $value]);
            Cache::forget("setting.{$setting->key}");
        }

        return $setting;
    }

    public function groupedSettings(): Collection
    {
        return Setting::query()
            ->orderBy('group')
            ->orderBy('id')
            ->get()
            ->groupBy('group');
    }
}
