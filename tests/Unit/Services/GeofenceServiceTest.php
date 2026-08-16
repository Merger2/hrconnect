<?php

use App\Exceptions\AntiFakeGPSException;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\GeofenceViolationException;
use App\Models\Branch;
use App\Models\Company;
use App\Services\Attendance\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Master data setup — mengikuti pola AttendanceServiceTest.
 * Branch HQ: (-6.2, 106.8) radius 500m.
 */
beforeEach(function () {
    $companyId = Company::create([
        'name' => 'PT Test',
        'code' => 'TST',
        'phone' => '021',
        'email' => 'test@test.com',
        'npwp' => '0',
        'is_active' => true,
    ])->id;

    $this->branch = Branch::create([
        'company_id' => $companyId,
        'name' => 'HQ',
        'address' => 'JKT',
        'is_main' => true,
        'is_active' => true,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 500,
    ]);

    $this->geofenceService = new GeofenceService;
});

// ─── Helpers ───────────────────────────────────────────────────────────

/**
 * GPS data dalam radius 500m dari HQ (-6.2, 106.8).
 * Offset lat 0.003° ≈ 334m — di dalam radius.
 */
function geoValidGps(array $overrides = []): array
{
    return array_merge([
        'latitude' => -6.197,
        'longitude' => 106.8,
        'accuracy' => 10,
        'gps_variance' => 0.001,
    ], $overrides);
}

// ═══════════════════════════════════════════════════════════════════════
// validateLocation() — dalam / luar radius
// ═══════════════════════════════════════════════════════════════════════

test('validateLocation returns valid result when within radius', function () {
    $result = $this->geofenceService->validateLocation($this->branch, geoValidGps());

    expect($result['valid'])->toBeTrue();
    expect($result['distance'])->toBeLessThan(500.0);
    expect($result['accuracy'])->toBe(10);
});

test('validateLocation returns valid result near radius boundary (longitude offset)', function () {
    // Pure longitude offset: lat = -6.2 (pusat), lng offset 0.004° ≈ 442m — masih di dalam radius 500m.
    // Catatan: jangan gabungkan dengan base lat offset (-6.197) karena kombinasi ≈ 554m > 500m.
    $result = $this->geofenceService->validateLocation($this->branch, geoValidGps([
        'latitude' => -6.2,
        'longitude' => 106.804,
    ]));

    expect($result['valid'])->toBeTrue();
    expect($result['distance'])->toBeLessThan(500.0);
});

test('validateLocation throws GeofenceViolationException when outside radius', function () {
    // Offset lat 0.006° ≈ 667m — di luar radius 500m
    expect(fn () => $this->geofenceService->validateLocation($this->branch, geoValidGps([
        'latitude' => -6.194,
    ])))->toThrow(GeofenceViolationException::class, 'luar jangkauan kantor');
});

test('validateLocation throws GeofenceViolationException when outside via longitude', function () {
    // Offset lng 0.006° ≈ 663m — di luar radius 500m
    expect(fn () => $this->geofenceService->validateLocation($this->branch, geoValidGps([
        'longitude' => 106.806,
    ])))->toThrow(GeofenceViolationException::class, 'luar jangkauan kantor');
});

// ═══════════════════════════════════════════════════════════════════════
// validateLocation() — anti fake GPS & akurasi
// ═══════════════════════════════════════════════════════════════════════

test('validateLocation throws AntiFakeGPSException when is_mocked is true', function () {
    expect(fn () => $this->geofenceService->validateLocation($this->branch, geoValidGps([
        'is_mocked' => true,
    ])))->toThrow(AntiFakeGPSException::class, 'Fake GPS terdeteksi');
});

test('validateLocation throws AntiFakeGPSException when accuracy is too low (>50)', function () {
    expect(fn () => $this->geofenceService->validateLocation($this->branch, geoValidGps([
        'accuracy' => 100,
    ])))->toThrow(AntiFakeGPSException::class, 'Akurasi GPS terlalu rendah');
});

test('validateLocation accepts accuracy exactly at threshold (50)', function () {
    $result = $this->geofenceService->validateLocation($this->branch, geoValidGps([
        'accuracy' => 50,
    ]));

    expect($result['valid'])->toBeTrue();
});

test('validateLocation throws AntiFakeGPSException when gps_variance is suspiciously low', function () {
    expect(fn () => $this->geofenceService->validateLocation($this->branch, geoValidGps([
        'gps_variance' => 0.0000005,
    ])))->toThrow(AntiFakeGPSException::class, 'Pergerakan GPS tidak wajar');
});

test('validateLocation accepts gps_variance exactly at threshold', function () {
    $result = $this->geofenceService->validateLocation($this->branch, geoValidGps([
        'gps_variance' => 0.000001,
    ]));

    expect($result['valid'])->toBeTrue();
});

// ═══════════════════════════════════════════════════════════════════════
// validateLocation() — branch tanpa koordinat / tanpa radius
// ═══════════════════════════════════════════════════════════════════════

test('validateLocation throws BusinessRuleException when branch has no coordinates', function () {
    $companyId = $this->branch->company_id;
    $noCoordBranch = Branch::create([
        'company_id' => $companyId,
        'name' => 'Cabang Tanpa Koordinat',
        'address' => 'XYZ',
        'is_main' => false,
        'is_active' => true,
        'latitude' => null,
        'longitude' => null,
        'radius' => 500,
    ]);

    expect(fn () => $this->geofenceService->validateLocation($noCoordBranch, geoValidGps()))
        ->toThrow(BusinessRuleException::class, 'belum punya koordinat GPS');
});

test('validateLocation throws BusinessRuleException when branch has no radius', function () {
    // branches.radius adalah NOT NULL DEFAULT 100 — simulasi tanpa radius via
    // attribute in-memory (cast integer mempertahankan null).
    $this->branch->radius = null;

    expect(fn () => $this->geofenceService->validateLocation($this->branch, geoValidGps()))
        ->toThrow(BusinessRuleException::class, 'belum punya radius geofence');
});

// ═══════════════════════════════════════════════════════════════════════
// validateLocation() — validasi koordinat GPS client
// ═══════════════════════════════════════════════════════════════════════

test('validateLocation throws BusinessRuleException when GPS coordinates are missing', function () {
    expect(fn () => $this->geofenceService->validateLocation($this->branch, [
        'accuracy' => 10,
    ]))->toThrow(BusinessRuleException::class, 'Koordinat GPS tidak dikirim');
});

test('validateLocation throws BusinessRuleException when GPS coordinates are not numeric', function () {
    expect(fn () => $this->geofenceService->validateLocation($this->branch, [
        'latitude' => 'abc',
        'longitude' => 106.8,
        'accuracy' => 10,
    ]))->toThrow(BusinessRuleException::class, 'Format koordinat GPS tidak valid');
});

test('validateLocation throws BusinessRuleException when GPS latitude out of range', function () {
    expect(fn () => $this->geofenceService->validateLocation($this->branch, [
        'latitude' => 91,
        'longitude' => 106.8,
        'accuracy' => 10,
    ]))->toThrow(BusinessRuleException::class, 'di luar range valid');
});

test('validateLocation throws BusinessRuleException when GPS longitude out of range', function () {
    expect(fn () => $this->geofenceService->validateLocation($this->branch, [
        'latitude' => -6.2,
        'longitude' => 181,
        'accuracy' => 10,
    ]))->toThrow(BusinessRuleException::class, 'di luar range valid');
});

test('validateLocation throws BusinessRuleException for null island (0,0) coordinates', function () {
    expect(fn () => $this->geofenceService->validateLocation($this->branch, [
        'latitude' => 0,
        'longitude' => 0,
        'accuracy' => 10,
    ]))->toThrow(BusinessRuleException::class, 'Koordinat GPS (0, 0)');
});

// ═══════════════════════════════════════════════════════════════════════
// validateGpsTimeSeries()
// ═══════════════════════════════════════════════════════════════════════

test('validateGpsTimeSeries returns silently for fewer than 2 samples', function () {
    // Panggil langsung — exception apa pun otomatis menggagalkan test.
    // (->not->toThrow() tanpa arg tidak didukung versi Pest project ini.)
    $this->geofenceService->validateGpsTimeSeries([
        ['latitude' => -6.2, 'longitude' => 106.8, 'timestamp' => 0],
    ]);

    expect(true)->toBeTrue();
});

test('validateGpsTimeSeries throws AntiFakeGPSException for stationary (zero variance) samples', function () {
    expect(fn () => $this->geofenceService->validateGpsTimeSeries([
        ['latitude' => -6.2, 'longitude' => 106.8, 'timestamp' => 0],
        ['latitude' => -6.2, 'longitude' => 106.8, 'timestamp' => 60],
    ]))->toThrow(AntiFakeGPSException::class, 'Pergerakan GPS tidak wajar');
});

test('validateGpsTimeSeries throws AntiFakeGPSException for implausible speed', function () {
    // 0.001° lat ≈ 111m dalam 1 detik ≈ 400 km/jam — tidak wajar
    expect(fn () => $this->geofenceService->validateGpsTimeSeries([
        ['latitude' => 0, 'longitude' => 0, 'timestamp' => 0],
        ['latitude' => 0.001, 'longitude' => 0, 'timestamp' => 1],
    ]))->toThrow(AntiFakeGPSException::class, 'Kecepatan pergerakan tidak wajar');
});

test('validateGpsTimeSeries passes for normal movement', function () {
    // ~1.1m dalam 60 detik ≈ 0.07 km/jam — wajar
    // Panggil langsung — exception apa pun otomatis menggagalkan test.
    $this->geofenceService->validateGpsTimeSeries([
        ['latitude' => -6.2, 'longitude' => 106.8, 'timestamp' => 0],
        ['latitude' => -6.20001, 'longitude' => 106.8, 'timestamp' => 60],
    ]);

    expect(true)->toBeTrue();
});
