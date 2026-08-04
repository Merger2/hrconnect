<?php

use App\Http\Controllers\Api\Device\PhotoUploadController;
use App\Services\Attendance\DeviceAttendanceService;
use Illuminate\Http\UploadedFile;

/**
 * DeviceAttendanceService adalah stub service yang saat ini mengembalikan
 * response dummy (attendance->id=0, slot='in'). Test ini memvalidasi bahwa
 * contract return type sesuai dengan yang diharapkan PhotoUploadController.
 *
 * @see PhotoUploadController
 */
beforeEach(function () {
    $this->service = new DeviceAttendanceService;
});

// ═══════════════════════════════════════════════════════════════════════
// Return Structure
// ═══════════════════════════════════════════════════════════════════════

test('uploadPhoto returns expected structure with int userId', function () {
    $result = $this->service->uploadPhoto(
        userId: 42,
        photo: 'base64-encoded-photo-data',
    );

    expect($result)->toBeArray();
    expect($result)->toHaveKey('attendance');
    expect($result)->toHaveKey('slot');

    // attendance adalah stdClass dengan id=0 (dummy)
    expect($result['attendance'])->toBeObject();
    expect($result['attendance']->id)->toBe(0);

    // slot default 'in'
    expect($result['slot'])->toBe('in');
});

test('uploadPhoto returns expected structure with string userId', function () {
    $result = $this->service->uploadPhoto(
        userId: 'ext-usr-abc-123',
        photo: 'binary-data',
    );

    expect($result)->toBeArray();
    expect($result['attendance']->id)->toBe(0);
    expect($result['slot'])->toBe('in');
});

// ═══════════════════════════════════════════════════════════════════════
// Type Handling — parameter type hints
// ═══════════════════════════════════════════════════════════════════════

test('uploadPhoto accepts string userId type', function () {
    $result = $this->service->uploadPhoto(
        userId: '550e8400-e29b-41d4-a716-446655440000',
        photo: 'photo-data',
    );

    expect($result['attendance']->id)->toBe(0);
});

test('uploadPhoto accepts UploadedFile as photo parameter', function () {
    // Simulasi data dari request->file('photo') seperti di PhotoUploadController
    $file = UploadedFile::fake()->image('selfie.jpg');

    $result = $this->service->uploadPhoto(
        userId: 1,
        photo: $file,
    );

    expect($result['attendance']->id)->toBe(0);
    expect($result['slot'])->toBe('in');
});

test('uploadPhoto accepts null photo parameter gracefully', function () {
    $result = $this->service->uploadPhoto(
        userId: 1,
        photo: null,
    );

    expect($result['attendance']->id)->toBe(0);
    expect($result['slot'])->toBe('in');
});

// ═══════════════════════════════════════════════════════════════════════
// GPS Coordinates
// ═══════════════════════════════════════════════════════════════════════

test('uploadPhoto accepts latitude and longitude', function () {
    $result = $this->service->uploadPhoto(
        userId: 1,
        photo: 'photo',
        latitude: -6.2088,
        longitude: 106.8456,
    );

    // Saat ini stub tidak memproses koordinat, tapi setidaknya
    // method signature menerima parameter tersebut tanpa error
    expect($result['attendance']->id)->toBe(0);
    expect($result['slot'])->toBe('in');
});

test('uploadPhoto accepts latitude without longitude', function () {
    $result = $this->service->uploadPhoto(
        userId: 1,
        photo: 'photo',
        latitude: -6.2088,
        // longitude = null (default)
    );

    expect($result['attendance']->id)->toBe(0);
});

test('uploadPhoto accepts longitude without latitude', function () {
    $result = $this->service->uploadPhoto(
        userId: 1,
        photo: 'photo',
        // latitude = null (default)
        longitude: 106.8456,
    );

    expect($result['attendance']->id)->toBe(0);
});

test('uploadPhoto accepts GPS coordinates as integers', function () {
    // Controller melakukan cast (float) validated['latitude'],
    // tapi bisa saja nilainya integer string.
    $result = $this->service->uploadPhoto(
        userId: 1,
        photo: 'photo',
        latitude: 0,
        longitude: 0,
    );

    // Stub tidak melakukan operasi apapun dengan koordinat,
    // jadi pastikan setidaknya tidak error.
    expect($result['attendance']->id)->toBe(0);
});

// ═══════════════════════════════════════════════════════════════════════
// PhotoUploadController contract — return value harus kompatibel
// ═══════════════════════════════════════════════════════════════════════

test('uploadPhoto return value is compatible with PhotoUploadController response building', function () {
    // PhotoUploadController mengakses:
    //   $result['attendance']->id  → untuk route('attendance.photo', [...])
    //   $result['slot']            → untuk menentukan type parameter

    $result = $this->service->uploadPhoto(
        userId: 99,
        photo: 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
        latitude: -6.2,
        longitude: 106.8,
    );

    // Contract assertions — ini harus selalu true selama stub belum berubah
    expect($result)->toHaveKeys(['attendance', 'slot']);
    expect(is_object($result['attendance']))->toBeTrue();
    expect(property_exists($result['attendance'], 'id'))->toBeTrue();
    expect($result['attendance']->id)->toBeInt();
    expect(in_array($result['slot'], ['in', 'out']))->toBeTrue();
});

// ═══════════════════════════════════════════════════════════════════════
// Edge Cases
// ═══════════════════════════════════════════════════════════════════════

test('uploadPhoto handles large string userId', function () {
    $result = $this->service->uploadPhoto(
        userId: str_repeat('a', 1000),
        photo: 'photo',
    );

    expect($result['attendance']->id)->toBe(0);
});

test('uploadPhoto handles empty string photo', function () {
    $result = $this->service->uploadPhoto(
        userId: 1,
        photo: '',
    );

    expect($result['attendance']->id)->toBe(0);
});

test('uploadPhoto handles special float values for GPS', function () {
    $result = $this->service->uploadPhoto(
        userId: 1,
        photo: 'photo',
        latitude: -90.0,   // min valid latitude
        longitude: 180.0,  // max valid longitude
    );

    expect($result['attendance']->id)->toBe(0);
});

test('uploadPhoto handles NaN GPS coordinates without crash', function () {
    $result = $this->service->uploadPhoto(
        userId: 1,
        photo: 'photo',
        latitude: NAN,
        longitude: NAN,
    );

    // Stub tidak melakukan operasi dengan coordinate,
    // jadi NAN tidak menyebabkan error.
    expect($result['attendance']->id)->toBe(0);
});
