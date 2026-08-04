<?php

use App\Contracts\AttendanceServiceInterface;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use App\Support\ApiTokenPermission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

test('admin can view subordinate attendance photo', function () {
    Storage::fake('local');

    $admin = User::factory()->admin()->create();
    $employee = User::factory()->create();
    $employeeRecord = Employee::factory()->create(['user_id' => $employee->id]);

    $path = 'attendance_photos/test/check-in.jpg';
    Storage::disk('local')->put($path, 'fake-image');

    $attendance = Attendance::create([
        'employee_id' => $employeeRecord->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'attachment' => json_encode(['in' => $path]),
    ]);

    $response = $this->actingAs($admin)->get("/attendance/photo/{$attendance->id}/in");

    $response->assertOk();
});

test('non admin cannot view another users attendance photo', function () {
    Storage::fake('public');

    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $path = 'attendance_photos/test/check-in.jpg';
    Storage::disk('public')->put($path, 'fake-image');

    $attendance = Attendance::create([
        'employee_id' => Employee::factory()->create(['user_id' => $owner->id])->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'attachment' => json_encode(['in' => $path]),
    ]);

    $response = $this->actingAs($otherUser)->get("/attendance/photo/{$attendance->id}/in");

    $response->assertForbidden();
});

test('enterprise attendance service returns secure attachment routes for multi-photo attachments', function () {

    app()->forgetInstance(AttendanceServiceInterface::class);

    $attendance = Attendance::create([
        'employee_id' => Employee::factory()->create()->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'attachment' => json_encode([
            'in' => 'attendance_photos/test/check-in.jpg',
            'out' => 'attendance_photos/test/check-out.jpg',
        ]),
    ]);

    $service = app(AttendanceServiceInterface::class);
    $urls = $service->getAttachmentUrl($attendance);

    expect($urls)->toBeArray()
        ->and($urls['in'])->toBe(route('attendance.attachment.download', ['attendance' => $attendance->id]))
        ->and($urls['out'])->toBe(route('attendance.attachment.download', ['attendance' => $attendance->id]));
});

test('attendance photo route rejects unsafe attachment paths', function () {
    $owner = User::factory()->create();

    $attendance = Attendance::create([
        'employee_id' => Employee::factory()->create(['user_id' => $owner->id])->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'attachment' => json_encode(['in' => '../secrets.txt']),
    ]);

    $response = $this->actingAs($owner)->get(route('attendance.photo', [
        'attendance' => $attendance->id,
        'type' => 'in',
    ]));

    $response->assertNotFound();
});

test('attendance photo public disk fallback is logged for legacy files', function () {
    config(['filesystems.attachment_disks' => ['local', 'public']]);
    Storage::fake('local');
    Storage::fake('public');
    Log::spy();

    $owner = User::factory()->create();
    $path = 'attendance_photos/legacy/check-in.jpg';
    Storage::disk('public')->put($path, 'legacy-image');

    $attendance = Attendance::create([
        'employee_id' => Employee::factory()->create(['user_id' => $owner->id])->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'attachment' => json_encode(['in' => $path]),
    ]);

    $this->actingAs($owner)
        ->get(route('attendance.photo', [
            'attendance' => $attendance->id,
            'type' => 'in',
        ]))
        ->assertOk();

    Log::shouldHaveReceived('warning')
        ->with('Serving attachment from legacy public disk fallback.', Mockery::on(fn (array $context): bool => ($context['path_basename'] ?? null) === 'check-in.jpg'
            && ($context['audit_action'] ?? null) === 'Attendance Photo Viewed'))
        ->once();
});

test('attendance photo attachment disk lookup can disable public legacy fallback', function () {
    config(['filesystems.attachment_disks' => ['local']]);
    Storage::fake('local');
    Storage::fake('public');

    $owner = User::factory()->create();
    $path = 'attendance_photos/legacy/check-in.jpg';
    Storage::disk('public')->put($path, 'legacy-image');

    $attendance = Attendance::create([
        'employee_id' => Employee::factory()->create(['user_id' => $owner->id])->id,
        'date' => now()->toDateString(),
        'status' => 'present',
        'attachment' => json_encode(['in' => $path]),
    ]);

    $this->actingAs($owner)
        ->get(route('attendance.photo', [
            'attendance' => $attendance->id,
            'type' => 'in',
        ]))
        ->assertNotFound();
});

test('device photo api stores attendance photo in attachment payload', function () {
    Storage::fake('local');

    $user = User::factory()->create();
    Sanctum::actingAs($user, deviceApiAbilities());

    $response = $this->post('/api/device/photo', [
        'photo' => UploadedFile::fake()->image('check-in.jpg'),
        'latitude' => -6.2,
        'longitude' => 106.8,
    ]);

    $response->assertOk();

    $attendance = Attendance::firstOrFail();
    $attachments = json_decode($attendance->attachment, true);

    expect($attachments)->toHaveKey('in')
        ->and(Storage::disk('local')->exists($attachments['in']))->toBeTrue()
        ->and($attendance->latitude_in)->toBe(-6.2)
        ->and($attendance->longitude_in)->toBe(106.8);
});

test('device photo api rejects dangerous double extension and oversized uploads', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, deviceApiAbilities());

    $this->post('/api/device/photo', [
        'photo' => UploadedFile::fake()->create('photo.php.jpg', 10, 'image/jpeg'),
        'latitude' => -6.2,
        'longitude' => 106.8,
    ])->assertInvalid(['photo']);

    $this->post('/api/device/photo', [
        'photo' => UploadedFile::fake()->image('too-large.jpg')->size(6 * 1024),
        'latitude' => -6.2,
        'longitude' => 106.8,
    ])->assertInvalid(['photo']);
});

test('device permissions api requires explicit permissions ability', function () {
    $user = User::factory()->create();

    Sanctum::actingAs($user, [ApiTokenPermission::DEVICE_LOCATION]);

    $this->getJson('/api/device/permissions')
        ->assertForbidden();

    Sanctum::actingAs($user, [ApiTokenPermission::DEVICE_PERMISSIONS]);

    $this->getJson('/api/device/permissions')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('permissions.camera.state', 'prompt');
});

test('device api rejects administrator personal access tokens', function () {
    $admin = User::factory()->admin()->create();

    Sanctum::actingAs($admin, [ApiTokenPermission::DEVICE_PERMISSIONS]);

    $this
        ->getJson('/api/device/permissions')
        ->assertForbidden()
        ->assertJsonPath('message', 'Device API is only available for employee accounts.');
});
