<?php

use App\Models\BpjsConfig;
use App\Models\CompanySetting;
use App\Models\Holiday;
use App\Models\TaxConfig;
use Illuminate\Cache\CacheManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class)->group('cache');

beforeEach(function () {
    $container = app();
    $container->forgetInstance('cache');
    $container->singleton('cache', fn ($app) => new CacheManager($app));
    Cache::clearResolvedInstance();
});

test('TaxConfig save clears cache via observer', function () {
    Cache::remember('tax_configs', 3600, fn () => ['cached']);
    expect(Cache::get('tax_configs'))->toBe(['cached']);

    TaxConfig::factory()->create([
        'ter_category' => 'A',
        'min_income' => 0,
        'max_income' => 1000000,
        'rate' => 0.5,
        'effective_rate' => 0.5,
    ]);

    expect(Cache::get('tax_configs'))->toBeNull();
});

test('TaxConfig cachedAll returns fresh data from DB', function () {
    TaxConfig::factory()->count(2)->create();

    $result = TaxConfig::cachedAll();
    expect($result)->toHaveCount(2)
        ->each->toHaveKeys(['ter_category', 'min_income', 'max_income', 'rate', 'effective_rate']);
});

test('CompanySetting get and set round-trip', function () {
    CompanySetting::set('test_key', 'round_trip_value');

    $value = CompanySetting::get('test_key');
    expect($value)->toBe('round_trip_value');
});

test('CompanySetting get returns default when key missing', function () {
    $value = CompanySetting::get('nonexistent', 'fallback');
    expect($value)->toBe('fallback');
});

test('CompanySetting save clears cached value via observer', function () {
    Cache::remember('settings:test_cache_clear', 3600, fn () => 'stale');
    expect(Cache::get('settings:test_cache_clear'))->toBe('stale');

    CompanySetting::factory()->create(['key' => 'test_cache_clear', 'value' => 'fresh']);

    expect(Cache::get('settings:test_cache_clear'))->toBeNull();
});

test('Holiday cachedYear returns active holidays for given year', function () {
    Holiday::factory()->create(['date' => '2026-06-17', 'is_active' => true]);
    Holiday::factory()->create(['date' => '2026-12-25', 'is_active' => true]);

    $result = Holiday::cachedYear(2026);
    expect($result)->toHaveCount(2)
        ->toContain('2026-06-17', '2026-12-25');
});

test('Holiday cachedYear excludes inactive holidays', function () {
    Holiday::factory()->create(['date' => '2026-06-17', 'is_active' => true]);
    Holiday::factory()->create(['date' => '2026-07-04', 'is_active' => false]);

    $result = Holiday::cachedYear(2026);
    expect($result)->toHaveCount(1)
        ->toContain('2026-06-17');
});

test('BpjsConfig cachedAll returns fresh data from DB', function () {
    BpjsConfig::factory()->count(3)->create();

    $result = BpjsConfig::cachedAll();
    expect($result)->toHaveCount(3)
        ->each->toHaveKeys(['name', 'employer_rate', 'employee_rate', 'ceiling']);
});
