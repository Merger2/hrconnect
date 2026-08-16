<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\Admin\SettingsManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('update persists setting and clears cache', function () {
    $service = app(SettingsManagementService::class);

    Setting::create(['key' => 'company_name', 'value' => 'PT Lama', 'group' => 'company']);
    Cache::put('setting.company_name', 'cached-old');

    $service->update('company_name', 'PT Daya Cipta Mandiri Solusi');

    expect(Setting::where('key', 'company_name')->value('value'))->toBe('PT Daya Cipta Mandiri Solusi')
        ->and(Cache::has('setting.company_name'))->toBeFalse();
});

test('update creates setting when key does not exist', function () {
    $service = app(SettingsManagementService::class);

    $service->update('new_key', 'value-1');

    expect(Setting::where('key', 'new_key')->exists())->toBeTrue();
});

test('grouped settings returns settings grouped by group ordered by id', function () {
    $service = app(SettingsManagementService::class);

    Setting::create(['key' => 'b_key', 'value' => '1', 'group' => 'b']);
    Setting::create(['key' => 'a_key', 'value' => '2', 'group' => 'a']);
    Setting::create(['key' => 'a_key_2', 'value' => '3', 'group' => 'a']);

    $grouped = $service->groupedSettings();

    expect($grouped->keys()->all())->toBe(['a', 'b'])
        ->and($grouped->get('a')->pluck('key')->all())->toBe(['a_key', 'a_key_2']);
});

test('updateValue updates setting value and clears its cache', function () {
    $service = app(SettingsManagementService::class);

    $setting = Setting::create(['key' => 'app_name', 'value' => 'Lama']);
    Cache::put('setting.app_name', 'cached');

    $service->updateValue($setting->id, 'Baru');

    expect($setting->fresh()->value)->toBe('Baru')
        ->and(Cache::has('setting.app_name'))->toBeFalse();
});
