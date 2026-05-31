<?php

use App\Enums\VerificationMethod;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRecognizedException;
use App\Exceptions\FaceNotRegisteredException;
use App\Exceptions\InvalidPinException;
use App\Models\Employee;
use App\Services\AttendanceService;
use App\Services\FaceRecognitionService;
use App\Services\GeofenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

/**
 * B12 fix verification — resolveVerification() tiered fallback Face → PIN → throw.
 *
 * Skenario yang ditest:
 * 1. Face match → return FACE_VERIFIED + similarity score
 * 2. Face tidak match (FaceNotRecognizedException) → fallback PIN → return PIN_VERIFIED
 * 3. Face belum register (FaceNotRegisteredException dari race) → fallback PIN
 * 4. Face belum register dari awal (no embedding) + PIN → return PIN_VERIFIED
 * 5. Face belum register + tidak ada PIN → throw BusinessRuleException
 * 6. Face tidak match + tidak ada PIN → throw BusinessRuleException
 *
 * Pakai reflection karena resolveVerification() private.
 */
function callResolveVerification(AttendanceService $service, Employee $employee, array $data): array
{
    $reflection = new ReflectionMethod($service, 'resolveVerification');

    return $reflection->invoke($service, $employee, $data);
}

/**
 * Bypass Eloquent attribute cast (pgvector) yang tidak available di SQLite.
 * Set langsung ke $attributes via reflection.
 */
function setRawAttribute(Employee $employee, string $key, mixed $value): void
{
    $ref = new ReflectionClass($employee);
    $prop = $ref->getProperty('attributes');
    $prop->setAccessible(true);
    $attrs = $prop->getValue($employee);
    $attrs[$key] = $value;
    $prop->setValue($employee, $attrs);
}

test('Face match → return FACE_VERIFIED dengan similarity score', function () {
    $faceService = mock(FaceRecognitionService::class);
    $faceService->shouldReceive('verifyFace')
        ->once()
        ->andReturn(['valid' => true, 'similarity_percentage' => 95.5]);

    $service = new AttendanceService(
        mock(GeofenceService::class),
        $faceService,
    );

    $employee = new Employee();
    setRawAttribute($employee, 'face_embedding', '[0.1,0.2,0.3]');

    $result = callResolveVerification($service, $employee, [
        'face_embedding' => [0.1, 0.2, 0.3],
    ]);

    expect($result)->toBe([VerificationMethod::FACE_VERIFIED->value, 95.5]);
});

test('Face tidak match + PIN valid → fallback ke PIN_VERIFIED', function () {
    $faceService = mock(FaceRecognitionService::class);
    $faceService->shouldReceive('verifyFace')
        ->once()
        ->andThrow(new FaceNotRecognizedException('Wajah tidak dikenali'));

    $service = new AttendanceService(
        mock(GeofenceService::class),
        $faceService,
    );

    $employee = new Employee();
    $employee->id = 99;
    setRawAttribute($employee, 'face_embedding', '[0.1,0.2,0.3]');
    $employee->pin = Hash::make('123456');
    $employee->setRelation('user', null);

    $result = callResolveVerification($service, $employee, [
        'face_embedding' => [0.9, 0.9, 0.9],
        'pin' => '123456',
    ]);

    expect($result)->toBe([VerificationMethod::PIN_VERIFIED->value, null]);
});

test('Face belum register (no embedding) + PIN valid → langsung PIN_VERIFIED', function () {
    $faceService = mock(FaceRecognitionService::class);
    // verifyFace tidak boleh dipanggil karena face_embedding null
    $faceService->shouldNotReceive('verifyFace');

    $service = new AttendanceService(
        mock(GeofenceService::class),
        $faceService,
    );

    $employee = new Employee();
    $employee->id = 99;
    // face_embedding biarkan null (default)
    $employee->pin = Hash::make('654321');
    $employee->setRelation('user', null);

    $result = callResolveVerification($service, $employee, [
        'pin' => '654321',
    ]);

    expect($result)->toBe([VerificationMethod::PIN_VERIFIED->value, null]);
});

test('Face race (FaceNotRegisteredException saat verify) + PIN → fallback PIN', function () {
    $faceService = mock(FaceRecognitionService::class);
    $faceService->shouldReceive('verifyFace')
        ->once()
        ->andThrow(new FaceNotRegisteredException('Embedding hilang'));

    $service = new AttendanceService(
        mock(GeofenceService::class),
        $faceService,
    );

    $employee = new Employee();
    $employee->id = 99;
    setRawAttribute($employee, 'face_embedding', '[0.1,0.2,0.3]');
    $employee->pin = Hash::make('111222');
    $employee->setRelation('user', null);

    $result = callResolveVerification($service, $employee, [
        'face_embedding' => [0.1, 0.2, 0.3],
        'pin' => '111222',
    ]);

    expect($result)->toBe([VerificationMethod::PIN_VERIFIED->value, null]);
});

test('Face belum register + tanpa PIN → throw BusinessRuleException', function () {
    $faceService = mock(FaceRecognitionService::class);
    $faceService->shouldNotReceive('verifyFace');

    $service = new AttendanceService(
        mock(GeofenceService::class),
        $faceService,
    );

    $employee = new Employee();
    // face_embedding null

    expect(fn () => callResolveVerification($service, $employee, []))
        ->toThrow(BusinessRuleException::class, 'Wajah Anda belum terdaftar');
});

test('Face tidak match + PIN salah → throw InvalidPinException', function () {
    $faceService = mock(FaceRecognitionService::class);
    $faceService->shouldReceive('verifyFace')
        ->once()
        ->andThrow(new FaceNotRecognizedException('Tidak match'));

    $service = new AttendanceService(
        mock(GeofenceService::class),
        $faceService,
    );

    $employee = new Employee();
    $employee->id = 99;
    setRawAttribute($employee, 'face_embedding', '[0.1,0.2,0.3]');
    $employee->pin = Hash::make('correct-pin');
    $employee->setRelation('user', null);

    expect(fn () => callResolveVerification($service, $employee, [
        'face_embedding' => [0.9, 0.9, 0.9],
        'pin' => 'wrong-pin',
    ]))
        ->toThrow(InvalidPinException::class);
});

test('Face match tapi PIN ada → tetap pakai face (Face takes priority)', function () {
    $faceService = mock(FaceRecognitionService::class);
    $faceService->shouldReceive('verifyFace')
        ->once()
        ->andReturn(['valid' => true, 'similarity_percentage' => 90.0]);

    $service = new AttendanceService(
        mock(GeofenceService::class),
        $faceService,
    );

    $employee = new Employee();
    setRawAttribute($employee, 'face_embedding', '[0.1,0.2,0.3]');

    $result = callResolveVerification($service, $employee, [
        'face_embedding' => [0.1, 0.2, 0.3],
        'pin' => 'whatever',
    ]);

    // Face menang — PIN tidak diperiksa
    expect($result[0])->toBe(VerificationMethod::FACE_VERIFIED->value);
    expect($result[1])->toBe(90.0);
});
