<?php

use App\Exceptions\AntiFakeGPSException;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\GeofenceViolationException;
use App\Models\Branch;
use App\Services\GeofenceService;

if (! function_exists('makeBranch')) {
    function makeBranch(?float $lat = -6.2, ?float $lng = 106.8, int $radius = 100): Branch
    {
        $branch = new Branch;
        $branch->id = 1;
        $branch->name = 'Test HQ';
        $branch->latitude = $lat;
        $branch->longitude = $lng;
        $branch->radius = $radius;

        return $branch;
    }
}

// ─── Anti-Fake GPS ─────────────────────────────────────────────────────

test('throws AntiFakeGPSException when is_mocked is true', function () {
    $svc = new GeofenceService;
    $branch = makeBranch();

    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => -6.2,
        'longitude' => 106.8,
        'is_mocked' => true,
    ]))->toThrow(AntiFakeGPSException::class, 'Fake GPS');
});

test('throws AntiFakeGPSException when accuracy exceeds 100 meters', function () {
    $svc = new GeofenceService;
    $branch = makeBranch();

    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => -6.2,
        'longitude' => 106.8,
        'accuracy' => 150,
    ]))->toThrow(AntiFakeGPSException::class, 'Akurasi GPS terlalu rendah');

    // boundary: 51 is > 50
    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => -6.2,
        'longitude' => 106.8,
        'accuracy' => 51,
    ]))->toThrow(AntiFakeGPSException::class);

    // boundary: 50 should be allowed
    $result = $svc->validateLocation($branch, [
        'latitude' => -6.2,
        'longitude' => 106.8,
        'accuracy' => 50,
    ]);
    expect($result['valid'])->toBeTrue();
});

// ─── Coordinate Validation ─────────────────────────────────────────────

test('throws BusinessRuleException when coordinates not sent', function () {
    $svc = new GeofenceService;
    $branch = makeBranch();

    expect(fn () => $svc->validateLocation($branch, []))
        ->toThrow(BusinessRuleException::class, 'Koordinat GPS tidak dikirim');
});

test('throws BusinessRuleException when coordinates are null', function () {
    $svc = new GeofenceService;
    $branch = makeBranch();

    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => null,
        'longitude' => null,
    ]))->toThrow(BusinessRuleException::class, 'Koordinat GPS tidak dikirim');
});

test('throws BusinessRuleException for non-numeric coordinates', function () {
    $svc = new GeofenceService;
    $branch = makeBranch();

    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => 'invalid',
        'longitude' => 'invalid',
    ]))->toThrow(BusinessRuleException::class, 'Format koordinat GPS tidak valid');
});

test('throws BusinessRuleException when latitude out of range', function () {
    $svc = new GeofenceService;
    $branch = makeBranch();

    // lat > 90
    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => 95.5,
        'longitude' => 106.8,
    ]))->toThrow(BusinessRuleException::class, 'di luar range valid');

    // lat < -90
    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => -95.5,
        'longitude' => 106.8,
    ]))->toThrow(BusinessRuleException::class, 'di luar range valid');

    // lng > 180
    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => -6.2,
        'longitude' => 190,
    ]))->toThrow(BusinessRuleException::class, 'di luar range valid');

    // lng < -180
    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => -6.2,
        'longitude' => -190,
    ]))->toThrow(BusinessRuleException::class, 'di luar range valid');
});

test('throws BusinessRuleException for Null Island (0, 0)', function () {
    $svc = new GeofenceService;
    $branch = makeBranch();

    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => 0,
        'longitude' => 0,
    ]))->toThrow(BusinessRuleException::class, '(0, 0)');
});

// ─── Branch Coordinates ────────────────────────────────────────────────

test('throws BusinessRuleException when branch has no coordinates', function () {
    $svc = new GeofenceService;
    $branch = makeBranch(lat: null, lng: null);

    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => -6.2,
        'longitude' => 106.8,
    ]))->toThrow(BusinessRuleException::class, 'belum punya koordinat GPS');
});

// ─── Valid / Within Radius ─────────────────────────────────────────────

test('returns valid true and distance float when within radius', function () {
    $svc = new GeofenceService;
    $branch = makeBranch(lat: -6.2, lng: 106.8, radius: 1000);

    $result = $svc->validateLocation($branch, [
        'latitude' => -6.2001,
        'longitude' => 106.8001,
    ]);

    expect($result['valid'])->toBeTrue();
    expect($result['distance'])->toBeFloat();
    expect($result['distance'])->toBeGreaterThan(0);
});

// ─── Outside Radius ────────────────────────────────────────────────────

test('throws GeofenceViolationException when outside radius', function () {
    $svc = new GeofenceService;
    $branch = makeBranch(lat: -6.2, lng: 106.8, radius: 50);

    // ~5km away
    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => -6.25,
        'longitude' => 106.85,
    ]))->toThrow(GeofenceViolationException::class);
});

test('GeofenceViolationException message includes formatted distance', function () {
    $svc = new GeofenceService;
    $branch = makeBranch(lat: -6.2, lng: 106.8, radius: 50);

    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => -6.25,
        'longitude' => 106.85,
    ]))->toThrow(GeofenceViolationException::class, 'di luar jangkauan');
});
