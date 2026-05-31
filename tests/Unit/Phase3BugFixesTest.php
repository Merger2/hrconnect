<?php

use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRegisteredException;
use App\Exceptions\GeofenceViolationException;
use App\Models\Branch;
use App\Models\Employee;
use App\Services\FaceRecognitionService;
use App\Services\GeofenceService;

/**
 * Bug fixes Phase 3 (Sesi 7) — focused unit tests.
 *
 * GeofenceService null/invalid coordinates guard
 * DomainException → BusinessRuleException (HTTP 422)
 * Employee tanpa position throw eksplisit
 * FaceRecognition vector dimension validation
 */

// ─── B3.2 GeofenceService null guard ──────────────────────────────────

function makeBranch(?float $lat = -6.2, ?float $lng = 106.8, int $radius = 100): Branch
{
    $branch = new Branch();
    $branch->id = 1;
    $branch->name = 'Test HQ';
    $branch->latitude = $lat;
    $branch->longitude = $lng;
    $branch->radius = $radius;

    return $branch;
}

test('B3.2: GeofenceService throw kalau koordinat tidak dikirim', function () {
    $svc = new GeofenceService();
    $branch = makeBranch();

    expect(fn () => $svc->validateLocation($branch, []))
        ->toThrow(BusinessRuleException::class, 'Koordinat GPS tidak dikirim');
});

test('B3.2: GeofenceService throw kalau koordinat null', function () {
    $svc = new GeofenceService();
    $branch = makeBranch();

    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => null,
        'longitude' => null,
    ]))
        ->toThrow(BusinessRuleException::class, 'Koordinat GPS tidak dikirim');
});

test('B3.2: GeofenceService throw kalau koordinat string non-numeric', function () {
    $svc = new GeofenceService();
    $branch = makeBranch();

    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => 'invalid',
        'longitude' => 'invalid',
    ]))
        ->toThrow(BusinessRuleException::class, 'Format koordinat GPS tidak valid');
});

test('B3.2: GeofenceService throw kalau lat di luar range (-90..90)', function () {
    $svc = new GeofenceService();
    $branch = makeBranch();

    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => 95.5,
        'longitude' => 106.8,
    ]))
        ->toThrow(BusinessRuleException::class, 'di luar range valid');
});

test('B3.2: GeofenceService throw kalau Null Island (0, 0)', function () {
    $svc = new GeofenceService();
    $branch = makeBranch();

    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => 0,
        'longitude' => 0,
    ]))
        ->toThrow(BusinessRuleException::class, '(0, 0)');
});

test('B3.2: GeofenceService throw kalau branch tanpa koordinat', function () {
    $svc = new GeofenceService();
    $branch = makeBranch(lat: null, lng: null);

    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => -6.2,
        'longitude' => 106.8,
    ]))
        ->toThrow(BusinessRuleException::class, 'belum punya koordinat GPS');
});

test('B3.2: GeofenceService validate koordinat valid dalam radius', function () {
    $svc = new GeofenceService();
    $branch = makeBranch(lat: -6.2, lng: 106.8, radius: 1000);

    $result = $svc->validateLocation($branch, [
        'latitude' => -6.2001,
        'longitude' => 106.8001,
    ]);

    expect($result['valid'])->toBeTrue();
    expect($result['distance'])->toBeFloat();
});

test('B3.2: GeofenceService throw GeofenceViolation kalau di luar radius', function () {
    $svc = new GeofenceService();
    $branch = makeBranch(lat: -6.2, lng: 106.8, radius: 50);

    // ~5km away
    expect(fn () => $svc->validateLocation($branch, [
        'latitude' => -6.25,
        'longitude' => 106.85,
    ]))
        ->toThrow(GeofenceViolationException::class);
});

// ─── FaceRecognition vector dimension validation ────────────────

test('B3.12: FaceRecognitionService throw kalau employee tanpa face_embedding', function () {
    $svc = new FaceRecognitionService();
    $emp = new Employee();
    // face_embedding null

    expect(fn () => $svc->verifyFace($emp, array_fill(0, 128, 0.1)))
        ->toThrow(FaceNotRegisteredException::class);
});

test('B3.12: FaceRecognitionService throw kalau vector bukan 128D', function () {
    $svc = new FaceRecognitionService();

    $emp = new Employee();
    // setRawAttribute via reflection (bypass pgvector cast).
    // PHP 8.5: setAccessible() deprecated — reflection accessible by default.
    $ref = new ReflectionClass($emp);
    $prop = $ref->getProperty('attributes');
    $prop->setValue($emp, ['face_embedding' => '[0.1, 0.2]']);

    // 64-dim vector → invalid
    expect(fn () => $svc->verifyFace($emp, array_fill(0, 64, 0.1)))
        ->toThrow(BusinessRuleException::class, 'harus 128D');

    // 256-dim vector → invalid
    expect(fn () => $svc->verifyFace($emp, array_fill(0, 256, 0.1)))
        ->toThrow(BusinessRuleException::class, 'harus 128D');

    // 0-dim → invalid
    expect(fn () => $svc->verifyFace($emp, []))
        ->toThrow(BusinessRuleException::class, 'harus 128D');
});

test('B3.12: FaceRecognitionService throw kalau vector ada non-numeric', function () {
    $svc = new FaceRecognitionService();

    $emp = new Employee();
    $ref = new ReflectionClass($emp);
    $prop = $ref->getProperty('attributes');
    $prop->setValue($emp, ['face_embedding' => '[0.1, 0.2]']);

    // 128-dim tapi ada string → invalid
    $vector = array_fill(0, 128, 0.1);
    $vector[50] = 'not-a-number';

    expect(fn () => $svc->verifyFace($emp, $vector))
        ->toThrow(BusinessRuleException::class, 'tidak valid');
});
