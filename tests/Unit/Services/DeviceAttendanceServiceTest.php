<?php

use App\Exceptions\BusinessRuleException;
use App\Http\Controllers\Api\Device\PhotoUploadController;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Services\Attendance\DeviceAttendanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * DeviceAttendanceService — implementasi nyata sejak 2026-08-16 (mock-miss
 * fix): uploadPhoto() melampirkan foto ke record attendance hari ini
 * (slot in/out), bukan stub yang mengembalikan attendance->id = 0.
 *
 * @see PhotoUploadController
 */
uses(TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->service = new DeviceAttendanceService;
});

// ═══════════════════════════════════════════════════════════════════════
// Perilaku nyata — lampirkan foto ke attendance hari ini
// ═══════════════════════════════════════════════════════════════════════

test('uploadPhoto attaches photo to today attendance with slot in', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $attendance = Attendance::create([
        'employee_id' => $employee->id,
        'date' => now()->toDateString(),
        'clock_in' => now(),
        'status' => 'present',
    ]);

    $result = $this->service->uploadPhoto($user->id, UploadedFile::fake()->image('check-in.jpg'));

    expect($result['attendance']->id)->toBe($attendance->id);
    expect($result['slot'])->toBe('in');

    $fresh = $attendance->fresh();
    expect($fresh->photo_selfie_in)->not->toBeNull();
    Storage::disk('local')->assertExists($fresh->photo_selfie_in);
});

test('uploadPhoto uses slot out when check-in photo already attached', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $attendance = Attendance::create([
        'employee_id' => $employee->id,
        'date' => now()->toDateString(),
        'clock_in' => now(),
        'clock_out' => now()->addHours(8),
        'status' => 'present',
        'photo_selfie_in' => 'attendance_photos/2026/08/16/check-in.jpg',
    ]);

    $result = $this->service->uploadPhoto($user->id, UploadedFile::fake()->image('check-out.jpg'));

    expect($result['attendance']->id)->toBe($attendance->id);
    expect($result['slot'])->toBe('out');
    expect($attendance->fresh()->photo_selfie_out)->not->toBeNull();
});

// ═══════════════════════════════════════════════════════════════════════
// Kegagalan eksplisit (no silent degradation)
// ═══════════════════════════════════════════════════════════════════════

test('uploadPhoto throws when user has no employee record', function () {
    $user = User::factory()->create();

    expect(fn () => $this->service->uploadPhoto($user->id, UploadedFile::fake()->image('x.jpg')))
        ->toThrow(BusinessRuleException::class, 'Akun tidak terhubung');
});

test('uploadPhoto throws when there is no attendance today', function () {
    $user = User::factory()->create();
    Employee::factory()->create(['user_id' => $user->id]);

    expect(fn () => $this->service->uploadPhoto($user->id, UploadedFile::fake()->image('x.jpg')))
        ->toThrow(BusinessRuleException::class, 'Tidak ada catatan absensi hari ini');
});

test('uploadPhoto rejects non-uploaded photo payload', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    Attendance::create([
        'employee_id' => $employee->id,
        'date' => now()->toDateString(),
        'clock_in' => now(),
        'status' => 'present',
    ]);

    expect(fn () => $this->service->uploadPhoto($user->id, 'base64-encoded-photo-data'))
        ->toThrow(BusinessRuleException::class, 'Foto absensi tidak valid');
});

// ═══════════════════════════════════════════════════════════════════════
// PhotoUploadController contract — return value tetap kompatibel
// ═══════════════════════════════════════════════════════════════════════

test('uploadPhoto return value is compatible with PhotoUploadController response building', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    Attendance::create([
        'employee_id' => $employee->id,
        'date' => now()->toDateString(),
        'clock_in' => now(),
        'status' => 'present',
    ]);

    // PhotoUploadController mengakses:
    //   $result['attendance']->id  → untuk route('attendance.photo', [...])
    //   $result['slot']            → untuk menentukan type parameter
    $result = $this->service->uploadPhoto($user->id, UploadedFile::fake()->image('selfie.jpg'));

    expect($result)->toHaveKeys(['attendance', 'slot']);
    expect($result['attendance']->id)->toBeInt();
    expect(in_array($result['slot'], ['in', 'out'], true))->toBeTrue();
});
