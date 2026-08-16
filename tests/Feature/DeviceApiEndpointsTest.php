<?php

declare(strict_types=1);

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

/**
 * Coverage gap P0: Capacitor Device API (jalur absensi mobile/offline).
 * Endpoint: /api/v1/device/{location,offline-attendance,photo,permissions}.
 * Semua di bawah auth:sanctum + abilities + EnsureEmployeeDeviceApiAccount.
 *
 * Foto (photo) dikonsolidasikan ke file ini 2026-08-16 setelah
 * DeviceAttendanceService diimplementasikan nyata (bukan stub attendance_id=0)
 * — sebelumnya test foto tersebar di AttendanceMediaAndApiTest.
 */
function deviceApiUser(): array
{
    $user = User::factory()->create(['group' => 'user']);
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    return [$user, $employee];
}

// ─── LOCATION ───

test('device location accepts valid coordinates', function () {
    [$user] = deviceApiUser();

    Sanctum::actingAs($user, ['device:location']);

    $this->postJson('/api/v1/device/location', [
        'latitude' => -6.2088,
        'longitude' => 106.8456,
        'accuracy' => 12.5,
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.latitude', -6.2088)
        ->assertJsonPath('data.accuracy', 12.5)
        ->assertJsonStructure(['data' => ['timestamp']]);
});

test('device location validates coordinate bounds', function () {
    [$user] = deviceApiUser();

    Sanctum::actingAs($user, ['device:location']);

    $this->postJson('/api/v1/device/location', [
        'latitude' => 200,
        'longitude' => 106.8456,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('latitude');
});

test('device location requires device:location token ability', function () {
    [$user] = deviceApiUser();

    Sanctum::actingAs($user, []);

    $this->postJson('/api/v1/device/location', [
        'latitude' => -6.2088,
        'longitude' => 106.8456,
    ])->assertForbidden();
});

test('device endpoints reject non-employee accounts', function () {
    $admin = User::factory()->admin(true)->create();

    Sanctum::actingAs($admin, ['device:location']);

    $this->postJson('/api/v1/device/location', [
        'latitude' => -6.2088,
        'longitude' => 106.8456,
    ])->assertForbidden()
        ->assertJsonPath('message', 'Device API is only available for employee accounts.');
});

// ─── OFFLINE ATTENDANCE SYNC ───

test('offline attendance sync creates clock-in and clock-out records', function () {
    [$user, $employee] = deviceApiUser();

    Sanctum::actingAs($user, ['device:offline-attendance']);

    $this->postJson('/api/v1/device/offline-attendance', [
        'items' => [
            [
                'event_type' => 'clock_in',
                'occurred_at' => '2026-08-01 08:00:00',
                'latitude' => -6.2088,
                'longitude' => 106.8456,
                'device_id' => 'device-001',
            ],
            [
                'event_type' => 'clock_out',
                'occurred_at' => '2026-08-01 17:00:00',
                'device_id' => 'device-001',
            ],
        ],
    ])->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'results')
        ->assertJsonPath('results.0.status', 'synced');

    $attendance = Attendance::where('employee_id', $employee->id)->first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->clock_in?->format('H:i'))->toBe('08:00')
        ->and($attendance->clock_out?->format('H:i'))->toBe('17:00')
        ->and($attendance->verification_method->value)->toBe('offline')
        ->and($attendance->device_fingerprint)->toBe('device-001');
});

test('offline attendance sync is idempotent per day for clock-in', function () {
    [$user, $employee] = deviceApiUser();

    Sanctum::actingAs($user, ['device:offline-attendance']);

    $payload = [
        'items' => [
            ['event_type' => 'clock_in', 'occurred_at' => '2026-08-01 08:00:00'],
        ],
    ];

    $this->postJson('/api/v1/device/offline-attendance', $payload)->assertOk();
    $this->postJson('/api/v1/device/offline-attendance', $payload)->assertOk();

    expect(Attendance::where('employee_id', $employee->id)->count())->toBe(1);
});

test('offline attendance sync validates event type', function () {
    [$user] = deviceApiUser();

    Sanctum::actingAs($user, ['device:offline-attendance']);

    $this->postJson('/api/v1/device/offline-attendance', [
        'items' => [
            ['event_type' => 'break', 'occurred_at' => '2026-08-01 10:00:00'],
        ],
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('items.0.event_type');
});

test('offline attendance sync caps items at 50 per request', function () {
    [$user] = deviceApiUser();

    Sanctum::actingAs($user, ['device:offline-attendance']);

    $items = collect(range(1, 51))->map(fn ($i) => [
        'event_type' => 'clock_in',
        'occurred_at' => "2026-08-01 {$i}:00:00",
    ])->all();

    $this->postJson('/api/v1/device/offline-attendance', ['items' => $items])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('items');
});

test('offline attendance sync returns empty results for user without employee record', function () {
    $user = User::factory()->create(['group' => 'user']);

    Sanctum::actingAs($user, ['device:offline-attendance']);

    $this->postJson('/api/v1/device/offline-attendance', [
        'items' => [
            ['event_type' => 'clock_in', 'occurred_at' => '2026-08-01 08:00:00'],
        ],
    ])->assertOk()
        ->assertJsonPath('results', []);
});

// ─── PHOTO ───

test('device photo api returns upload contract for employee accounts', function () {
    Storage::fake('local');

    [$user, $employee] = deviceApiUser();
    $attendance = Attendance::create([
        'employee_id' => $employee->id,
        'date' => now()->toDateString(),
        'clock_in' => now(),
        'status' => 'present',
    ]);

    Sanctum::actingAs($user, deviceApiAbilities());

    $response = $this->post('/api/v1/device/photo', [
        'photo' => UploadedFile::fake()->image('check-in.jpg'),
        'latitude' => -6.2,
        'longitude' => 106.8,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('attendance_id', $attendance->id)
        ->assertJsonPath('path', '/attendance/photo/'.$attendance->id.'/in');

    $fresh = $attendance->fresh();
    expect($fresh->photo_selfie_in)->not->toBeNull();
    Storage::disk('local')->assertExists($fresh->photo_selfie_in);
});

test('device photo api attaches to slot out when check-in photo exists', function () {
    Storage::fake('local');

    [$user, $employee] = deviceApiUser();
    $attendance = Attendance::create([
        'employee_id' => $employee->id,
        'date' => now()->toDateString(),
        'clock_in' => now(),
        'clock_out' => now()->addHours(8),
        'status' => 'present',
        'photo_selfie_in' => 'attendance_photos/2026/08/16/check-in.jpg',
    ]);

    Sanctum::actingAs($user, deviceApiAbilities());

    $response = $this->post('/api/v1/device/photo', [
        'photo' => UploadedFile::fake()->image('check-out.jpg'),
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('attendance_id', $attendance->id)
        ->assertJsonPath('path', '/attendance/photo/'.$attendance->id.'/out');

    expect($attendance->fresh()->photo_selfie_out)->not->toBeNull();
});

test('device photo api fails explicitly when there is no attendance today', function () {
    Storage::fake('local');

    [$user] = deviceApiUser();

    Sanctum::actingAs($user, deviceApiAbilities());

    $response = $this->post('/api/v1/device/photo', [
        'photo' => UploadedFile::fake()->image('check-in.jpg'),
    ]);

    // No silent degradation: tanpa attendance hari ini, endpoint menolak
    // eksplisit (bukan sukses palsu attendance_id=0).
    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Failed to upload photo.')
        ->assertJsonPath('reason', 'Tidak ada catatan absensi hari ini untuk melampirkan foto.');
});

test('device photo api fails explicitly when user has no employee record', function () {
    Storage::fake('local');

    $user = User::factory()->create(['group' => 'user']);

    Sanctum::actingAs($user, deviceApiAbilities());

    $response = $this->post('/api/v1/device/photo', [
        'photo' => UploadedFile::fake()->image('check-in.jpg'),
    ]);

    $response->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('reason', 'Akun tidak terhubung dengan data karyawan.');
});

test('device photo api rejects non-image and oversized uploads', function () {
    [$user] = deviceApiUser();

    Sanctum::actingAs($user, deviceApiAbilities());

    // Non-image MIME ditolak oleh rule mimes:jpg,jpeg,png.
    $this->post('/api/v1/device/photo', [
        'photo' => UploadedFile::fake()->create('malware.exe', 10, 'application/x-msdownload'),
        'latitude' => -6.2,
        'longitude' => 106.8,
    ])->assertInvalid(['photo']);

    // Upload > 5 MB ditolak oleh rule max:5120.
    $this->post('/api/v1/device/photo', [
        'photo' => UploadedFile::fake()->image('too-large.jpg')->size(6 * 1024),
        'latitude' => -6.2,
        'longitude' => 106.8,
    ])->assertInvalid(['photo']);
});

test('device photo api requires device:photo token ability', function () {
    [$user] = deviceApiUser();

    Sanctum::actingAs($user, []);

    $this->post('/api/v1/device/photo', [
        'photo' => UploadedFile::fake()->image('check-in.jpg'),
    ])->assertForbidden();
});

// ─── PERMISSIONS ───

test('device permissions endpoint returns camera and geolocation states', function () {
    [$user] = deviceApiUser();

    Sanctum::actingAs($user, ['device:permissions']);

    $this->getJson('/api/v1/device/permissions')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('permissions.camera.state', 'prompt')
        ->assertJsonPath('permissions.geolocation.state', 'prompt');
});
