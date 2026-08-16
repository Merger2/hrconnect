<?php

use App\Support\ApiTokenPermission;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase;
use Illuminate\Support\Facades\Config;
use Laravel\Fortify\Features as FortifyFeatures;
use Laravel\Jetstream\Features as JetstreamFeatures;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()->extend()" method to use the Laravel Testing
| environment classes which provide you with access to helpers like "refreshDatabase".
|
*/

pest()->extend(TestCase::class)
    ->in('Unit')
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Database Isolation
|--------------------------------------------------------------------------
|
| Feature tests share a single PostgreSQL test database (hris_testing).
| Apply RefreshDatabase globally to every Feature test so no test leaks
| rows into another — without this, tests only pass when run in isolation
| and the suite is order-dependent.
|
*/

uses(RefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function makes it easy to chain multiple assertions together in a readable way.
|
*/

// expect()->extend('toBeOne', fn () => $this->toBe(1));

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Enable Jetstream API-token features for tests that exercise the
 * /user/api-tokens UI (CreateApiTokenTest, DeleteApiTokenTest,
 * ApiTokenPermissionsTest).
 *
 * Jetstream v5 config/fortify.php already lists api(), but the feature flag
 * must be reflected in config('jetstream.features') at runtime for
 * Features::hasApiFeatures() to return true. Teams stay disabled because this
 * project ships no Team model (migrations exist but no model/UI).
 */
function enableJetstreamApiFeaturesForTests(): void
{
    $features = collect(config('jetstream.features', []))
        ->push(JetstreamFeatures::api())
        ->reject(fn ($feature) => $feature === JetstreamFeatures::teams())
        ->unique()
        ->values();

    Config::set('jetstream.features', $features->all());
}

/**
 * Enable Fortify email-verification for tests that exercise the
 * /email/verify flow. Feature is enabled by default in config/fortify.php,
 * but keep the flag explicit so the tests stay green regardless of env.
 */
function enableFortifyEmailVerificationForTests(): void
{
    $features = collect(config('fortify.features', []))
        ->push(FortifyFeatures::emailVerification())
        ->unique()
        ->values();

    Config::set('fortify.features', $features->all());
}

/**
 * All device-token abilities granted to an authenticated device client
 * (used by AttendanceMediaAndApiTest for Sanctum::actingAs).
 */
function deviceApiAbilities(): array
{
    return [
        ApiTokenPermission::DEVICE_LOCATION,
        ApiTokenPermission::DEVICE_OFFLINE_ATTENDANCE,
        ApiTokenPermission::DEVICE_PHOTO,
        ApiTokenPermission::DEVICE_PERMISSIONS,
    ];
}
