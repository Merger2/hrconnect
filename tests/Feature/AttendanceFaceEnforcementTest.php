<?php

use App\Contracts\AttendanceServiceInterface;
use App\Livewire\Admin\Settings;
use App\Livewire\User\HomeAttendanceStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\Setting;
use App\Models\Shift;
use App\Models\User;
use App\Services\Attendance\CommunityService;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;

test('admin settings shows both face id attendance toggles', function () {
    $admin = User::factory()->admin(superadmin: true)->create();

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        [
            'value' => '0',
            'group' => 'attendance',
            'type' => 'boolean',
            'description' => 'Require Face ID enrollment before attendance',
        ]
    );
    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_verification'],
        [
            'value' => '1',
            'group' => 'attendance',
            'type' => 'boolean',
            'description' => 'Require Face ID verification during attendance capture',
        ]
    );

    Livewire::actingAs($admin)
        ->test(Settings::class)
        ->assertSee('attendance.require_face_verification')
        ->assertSee('attendance.require_face_enrollment');
});

test('community service uses dedicated face enrollment setting instead of require photo setting', function () {
    Setting::updateOrCreate(
        ['key' => 'feature.require_photo'],
        ['value' => '1', 'group' => 'features', 'type' => 'boolean']
    );

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean']
    );

    Setting::flushCache('feature.require_photo');
    Setting::flushCache('attendance.require_face_enrollment');

    $service = new CommunityService;

    expect($service->shouldEnforceFaceEnrollment())->toBeFalse();

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        ['value' => '1', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::flushCache('attendance.require_face_enrollment');

    expect($service->shouldEnforceFaceEnrollment())->toBeTrue();
});

test('home attendance status requires face enrollment when face verification is enabled', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_enrollment'],
        ['value' => '0', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::updateOrCreate(
        ['key' => 'attendance.require_face_verification'],
        ['value' => '1', 'group' => 'attendance', 'type' => 'boolean']
    );
    Setting::flushCache('attendance.require_face_enrollment');
    Setting::flushCache('attendance.require_face_verification');

    app()->instance(AttendanceServiceInterface::class, new class implements AttendanceServiceInterface
    {
        public function storeAttachment(UploadedFile $file): string
        {
            return 'ignored';
        }

        public function getAttachmentUrl(Attendance $attendance): string|array|null
        {
            return null;
        }

        public function shouldEnforceFaceEnrollment(): bool
        {
            return false;
        }

        public function storeAttendancePhoto(string $base64Data, string $filename): string
        {
            return $filename;
        }

        public function registerFace(User $user, array $descriptor): void {}

        public function removeFace(User $user): void {}

        public function checkIn(array $data): mixed
        {
            return null;
        }

        public function checkOut(array $data): mixed
        {
            return null;
        }

        public function getRiskScore(int $employeeId): float
        {
            return 0.0;
        }

        public function isGeofenceValid(int $employeeId, float $lat, float $lng): bool
        {
            return true;
        }
    });

    Livewire::test(HomeAttendanceStatus::class)
        ->assertSet('requiresFaceEnrollment', true);
});

test('home attendance status ignores pending overtime for active overtime label', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Overtime::create([
        'employee_id' => Employee::factory()->create(['user_id' => $user->id])->id,
        'date' => now()->toDateString(),
        'start_time' => now()->setTime(18, 0),
        'end_time' => now()->setTime(20, 0),
        'total_hours' => 2,
        'description' => 'Need extra work tonight',
        'status' => 'pending',
    ]);

    Livewire::test(HomeAttendanceStatus::class)
        ->assertSet('hasApprovedOvertime', false);
});

test('home attendance status keeps approved overtime for active overtime label', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Overtime::create([
        'employee_id' => Employee::factory()->create(['user_id' => $user->id])->id,
        'date' => now()->toDateString(),
        'start_time' => now()->setTime(18, 0),
        'end_time' => now()->setTime(20, 0),
        'total_hours' => 2,
        'description' => 'Approved overtime',
        'status' => 'approved',
    ]);

    Livewire::test(HomeAttendanceStatus::class)
        ->assertSet('hasApprovedOvertime', true);
});

test('home attendance status falls back to morning shift when no schedule is defined', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Shift::create(['name' => 'Shift Sore', 'start_time' => '15:00', 'end_time' => '23:00']);
    Shift::create(['name' => 'Shift Pagi', 'start_time' => '07:00', 'end_time' => '15:00']);

    Livewire::test(HomeAttendanceStatus::class)
        ->assertSet('todayShiftSummary.name', 'Shift Pagi')
        ->assertSet('todayShiftSummary.duration', '8 hours');
});
