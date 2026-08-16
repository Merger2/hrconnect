<?php

use App\Enums\AttendanceStatus;
use App\Livewire\Admin\AttendanceCorrectionManager;
use App\Livewire\User\AttendanceCorrectionPage;
use App\Livewire\User\TeamApprovals;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Division;
use App\Models\Employee;
use App\Models\JobLevel;
use App\Models\JobTitle;
use App\Models\Position;
use App\Models\Role;
use App\Models\Shift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
use App\Notifications\AttendanceCorrectionStatusUpdated;
use App\Support\AttendanceCorrectionService;
use App\Support\TeamApprovalQueryService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

function createAttendanceApprovalHierarchy(): array
{
    $division = Division::factory()->create(['name' => 'Operations']);
    $managerLevel = JobLevel::create(['name' => 'Manager', 'rank' => 2]);
    $staffLevel = JobLevel::create(['name' => 'Staff', 'rank' => 4]);

    $managerTitle = JobTitle::create([
        'name' => 'Operations Manager',
        'job_level_id' => $managerLevel->id,
        'division_id' => $division->id,
    ]);

    $staffTitle = JobTitle::create([
        'name' => 'Operations Staff',
        'job_level_id' => $staffLevel->id,
        'division_id' => $division->id,
    ]);

    // employees.position_id FK menunjuk ke tabel `positions` (bukan job_titles).
    $managerPosition = Position::create(['name' => 'Ops Manager Position', 'code' => 'POS_'.uniqid(), 'division_id' => $division->id]);
    $staffPosition = Position::create(['name' => 'Ops Staff Position', 'code' => 'POS_'.uniqid(), 'division_id' => $division->id]);

    // Manager membutuhkan permission review_subordinate_requests agar
    // Gate::authorize('reviewSubordinateRequests') di TeamApprovals::mount()
    // lolos — pola sama dengan ApprovalWorkflowTest.
    $managerRole = Role::query()->create([
        'name' => 'Supervisor_'.uniqid(),
        'slug' => 'supervisor_'.uniqid(),
        'permission_keys' => ['review_subordinate_requests'],
    ]);

    $manager = User::factory()->create();
    $manager->roles()->sync([$managerRole->id]);
    $managerEmployee = Employee::factory()->create([
        'user_id' => $manager->id,
        'division_id' => $division->id,
        'position_id' => $managerPosition->id,
    ]);

    $employee = User::factory()->create(['manager_id' => $manager->id]);
    $employeeEmployee = Employee::factory()->create([
        'user_id' => $employee->id,
        'division_id' => $division->id,
        'position_id' => $staffPosition->id,
        'parent_id' => $managerEmployee->id,
    ]);

    return [$manager, $employee, $managerEmployee, $employeeEmployee];
}

test('employee with supervisor submits an attendance correction for manager review', function () {
    [, $user, , $employeeEmployee] = createAttendanceApprovalHierarchy();
    $shift = Shift::factory()->create([
        'name' => 'Pagi',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);

    Attendance::create([
        'employee_id' => $employeeEmployee->id,
        'date' => now()->toDateString(),
        'clock_in' => Carbon::parse(now()->toDateString().' 08:03:00'),
        'shift_id' => $shift->id,
        'status' => AttendanceStatus::ON_TIME,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceCorrectionPage::class)
        ->call('create')
        ->set('attendanceDate', now()->toDateString())
        ->set('includeRequestedTimeOut', true)
        ->set('requestedTimeOut', now()->toDateString().' 17:12')
        ->set('reason', 'Forgot to check out after field coordination.')
        ->call('save')
        ->assertHasNoErrors();

    $correction = AttendanceCorrection::query()->first();

    expect($correction)->not->toBeNull()
        ->and($correction->user_id)->toBe($user->id)
        ->and($correction->request_type)->toBe(AttendanceCorrection::TYPE_MISSING_CHECK_OUT)
        ->and($correction->status)->toBe(AttendanceCorrection::STATUS_PENDING)
        ->and($correction->attendance_id)->not->toBeNull()
        ->and($correction->current_snapshot)->not->toBeNull();
});

test('employee without supervisor submits an attendance correction directly to admin review', function () {
    $user = User::factory()->create();
    $employeeEmployee = Employee::factory()->create(['user_id' => $user->id]);
    $shift = Shift::factory()->create([
        'name' => 'Pagi',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);

    Attendance::create([
        'employee_id' => $employeeEmployee->id,
        'date' => now()->toDateString(),
        'clock_in' => Carbon::parse(now()->toDateString().' 08:03:00'),
        'shift_id' => $shift->id,
        'status' => AttendanceStatus::ON_TIME,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceCorrectionPage::class)
        ->call('create')
        ->set('attendanceDate', now()->toDateString())
        ->set('includeRequestedTimeOut', true)
        ->set('requestedTimeOut', now()->toDateString().' 17:12')
        ->set('reason', 'Forgot to check out after field coordination.')
        ->call('save')
        ->assertHasNoErrors();

    expect(AttendanceCorrection::query()->first()?->status)->toBe(AttendanceCorrection::STATUS_PENDING_ADMIN);
});

test('employee can request check in and check out corrections together in one submission', function () {
    [, $user, , $employeeEmployee] = createAttendanceApprovalHierarchy();
    $shift = Shift::factory()->create([
        'name' => 'Pagi',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);

    Attendance::create([
        'employee_id' => $employeeEmployee->id,
        'date' => now()->toDateString(),
        'shift_id' => $shift->id,
        'status' => AttendanceStatus::ON_TIME,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceCorrectionPage::class)
        ->call('create')
        ->set('attendanceDate', now()->toDateString())
        ->set('includeRequestedTimeIn', true)
        ->set('requestedTimeIn', now()->toDateString().' 08:05')
        ->set('includeRequestedTimeOut', true)
        ->set('requestedTimeOut', now()->toDateString().' 17:16')
        ->set('reason', 'Both check in and check out were not captured correctly.')
        ->call('save')
        ->assertHasNoErrors();

    $correction = AttendanceCorrection::query()->first();

    expect($correction)->not->toBeNull()
        ->and($correction->request_type)->toBe(AttendanceCorrection::TYPE_WRONG_TIME)
        ->and($correction->requested_time_in?->format('H:i:s'))->toBe('08:05:00')
        ->and($correction->requested_time_out?->format('H:i:s'))->toBe('17:16:00');
});

test('employee can request overnight check in and check out corrections', function () {
    [, $user, , $employeeEmployee] = createAttendanceApprovalHierarchy();
    $shift = Shift::factory()->create([
        'name' => 'Malam',
        'start_time' => '23:00:00',
        'end_time' => '07:00:00',
    ]);

    Attendance::create([
        'employee_id' => $employeeEmployee->id,
        'date' => now()->toDateString(),
        'shift_id' => $shift->id,
        'status' => AttendanceStatus::ON_TIME,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceCorrectionPage::class)
        ->call('create')
        ->set('attendanceDate', now()->toDateString())
        ->set('includeRequestedTimeIn', true)
        ->set('requestedTimeIn', now()->toDateString().' 23:05')
        ->set('includeRequestedTimeOut', true)
        ->set('requestedTimeOut', now()->copy()->addDay()->toDateString().' 07:10')
        ->set('reason', 'Night shift attendance was not captured correctly.')
        ->call('save')
        ->assertHasNoErrors();

    $correction = AttendanceCorrection::query()->first();

    expect($correction)->not->toBeNull()
        ->and($correction->requested_time_in?->format('Y-m-d H:i:s'))->toBe(now()->toDateString().' 23:05:00')
        ->and($correction->requested_time_out?->format('Y-m-d H:i:s'))->toBe(now()->copy()->addDay()->toDateString().' 07:10:00');
});

test('employee can request overnight check out correction from existing check in', function () {
    [, $user, , $employeeEmployee] = createAttendanceApprovalHierarchy();
    $shift = Shift::factory()->create([
        'name' => 'Sore',
        'start_time' => '16:00:00',
        'end_time' => '00:00:00',
    ]);

    Attendance::create([
        'employee_id' => $employeeEmployee->id,
        'date' => now()->toDateString(),
        'clock_in' => Carbon::parse(now()->toDateString().' 16:02:00'),
        'shift_id' => $shift->id,
        'status' => AttendanceStatus::ON_TIME,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceCorrectionPage::class)
        ->call('create')
        ->set('attendanceDate', now()->toDateString())
        ->set('includeRequestedTimeOut', true)
        ->set('requestedTimeOut', now()->copy()->addDay()->toDateString().' 00:00')
        ->set('reason', 'Evening shift checkout happened after midnight.')
        ->call('save')
        ->assertHasNoErrors();

    $correction = AttendanceCorrection::query()->first();

    expect($correction)->not->toBeNull()
        ->and($correction->requested_time_out?->format('Y-m-d H:i:s'))->toBe(now()->copy()->addDay()->toDateString().' 00:00:00');
});

test('attendance correction datetime defaults follow attendance date and shift', function () {
    [, $user, , $employeeEmployee] = createAttendanceApprovalHierarchy();
    $shift = Shift::factory()->create([
        'name' => 'Malam',
        'start_time' => '23:00:00',
        'end_time' => '07:00:00',
    ]);

    Attendance::create([
        'employee_id' => $employeeEmployee->id,
        'date' => now()->toDateString(),
        'shift_id' => $shift->id,
        'status' => AttendanceStatus::ON_TIME,
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceCorrectionPage::class)
        ->call('create')
        ->set('attendanceDate', now()->toDateString())
        ->set('includeRequestedTimeIn', true)
        ->assertSet('requestedTimeIn', now()->toDateString().' 23:00')
        ->set('includeRequestedTimeOut', true)
        ->assertSet('requestedTimeOut', now()->copy()->addDay()->toDateString().' 07:00');
});

test('attendance correction normalizes seeded datetime mismatch to attendance date', function () {
    [, $user, , $employeeEmployee] = createAttendanceApprovalHierarchy();
    $attendanceDate = '2026-04-01';
    $seededAtDate = '2026-04-24';
    $shift = Shift::factory()->create([
        'name' => 'Shift Sore',
        'start_time' => '15:00:00',
        'end_time' => '23:00:00',
    ]);

    Attendance::create([
        'employee_id' => $employeeEmployee->id,
        'date' => $attendanceDate,
        'shift_id' => $shift->id,
        'clock_in' => Carbon::parse($seededAtDate.' 15:08:00'),
        'clock_out' => Carbon::parse($seededAtDate.' 23:05:00'),
        'status' => 'late',
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceCorrectionPage::class)
        ->call('create')
        ->set('attendanceDate', $attendanceDate)
        ->assertViewHas('snapshotTimeIn', fn ($value) => $value?->format('Y-m-d H:i:s') === '2026-04-01 15:08:00')
        ->assertViewHas('snapshotTimeOut', fn ($value) => $value?->format('Y-m-d H:i:s') === '2026-04-01 23:05:00')
        ->set('includeRequestedTimeIn', true)
        ->assertSet('requestedTimeIn', '2026-04-01 15:08')
        ->set('includeRequestedTimeOut', true)
        ->assertSet('requestedTimeOut', '2026-04-01 23:05');
});

test('supervisor approval forwards attendance correction to admin and keeps it in history', function () {
    Notification::fake();

    [$manager, $user, , $employeeEmployee] = createAttendanceApprovalHierarchy();
    $shift = Shift::factory()->create([
        'name' => 'Shift Reguler',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);

    $attendance = Attendance::create([
        'employee_id' => $employeeEmployee->id,
        'date' => now()->toDateString(),
        'shift_id' => $shift->id,
        'clock_in' => Carbon::parse(now()->toDateString().' 08:00:00'),
        'clock_out' => Carbon::parse(now()->toDateString().' 17:00:00'),
        'status' => AttendanceStatus::ON_TIME,
    ]);

    $correction = AttendanceCorrection::create([
        'employee_id' => $employeeEmployee->id,
        'user_id' => $user->id,
        'attendance_id' => $attendance->id,
        'attendance_date' => now()->toDateString(),
        'request_type' => AttendanceCorrection::TYPE_WRONG_TIME,
        'requested_time_in' => Carbon::parse(now()->toDateString().' 08:25:00'),
        'requested_time_out' => Carbon::parse(now()->toDateString().' 17:20:00'),
        'requested_shift_id' => $shift->id,
        'reason' => 'Device sync was delayed during morning check in.',
        'status' => AttendanceCorrection::STATUS_PENDING,
    ]);

    $this->actingAs($manager);

    Livewire::test(TeamApprovals::class)
        ->set('activeTab', 'attendance-corrections')
        ->call('approveAttendanceCorrection', $correction->id);

    $correction->refresh();

    expect($correction->status)->toBe(AttendanceCorrection::STATUS_PENDING_ADMIN)
        ->and($correction->head_approved_by)->toBe($manager->id)
        ->and($correction->head_approved_at)->not->toBeNull()
        ->and($correction->reviewed_by)->toBeNull();

    $history = collect(app(TeamApprovalQueryService::class)->history($manager, 'attendance-corrections')->items());

    expect($history->pluck('id'))->toContain($correction->id);

    Notification::assertSentTo($user, AttendanceCorrectionStatusUpdated::class);
});

test('admin approval applies the attendance correction and notifies employee', function () {
    Notification::fake();

    $admin = User::factory()->admin(true)->create();
    [, $user, , $employeeEmployee] = createAttendanceApprovalHierarchy();
    $shift = Shift::factory()->create([
        'name' => 'Shift Reguler',
        'start_time' => '08:00:00',
        'end_time' => '17:00:00',
    ]);

    $attendance = Attendance::create([
        'employee_id' => $employeeEmployee->id,
        'date' => now()->toDateString(),
        'shift_id' => $shift->id,
        'clock_in' => Carbon::parse(now()->toDateString().' 08:00:00'),
        'clock_out' => Carbon::parse(now()->toDateString().' 17:00:00'),
        'status' => AttendanceStatus::ON_TIME,
    ]);

    $correction = AttendanceCorrection::create([
        'employee_id' => $employeeEmployee->id,
        'user_id' => $user->id,
        'attendance_id' => $attendance->id,
        'attendance_date' => now()->toDateString(),
        'request_type' => AttendanceCorrection::TYPE_WRONG_TIME,
        'requested_time_in' => Carbon::parse(now()->toDateString().' 08:25:00'),
        'requested_time_out' => Carbon::parse(now()->toDateString().' 17:20:00'),
        'requested_shift_id' => $shift->id,
        'reason' => 'Device sync was delayed during morning check in.',
        'status' => AttendanceCorrection::STATUS_PENDING_ADMIN,
    ]);

    $this->actingAs($admin);

    Livewire::test(AttendanceCorrectionManager::class)
        ->call('approve', $correction->id);

    $correction->refresh();
    $attendance->refresh();

    expect($correction->status)->toBe(AttendanceCorrection::STATUS_APPROVED)
        ->and($correction->reviewed_by)->toBe($admin->id)
        ->and($correction->reviewed_at)->not->toBeNull()
        ->and($attendance->time_in?->format('H:i:s'))->toBe('08:25:00')
        ->and($attendance->time_out?->format('H:i:s'))->toBe('17:20:00')
        ->and($attendance->status)->toBe(AttendanceStatus::LATE);

    Notification::assertSentTo($user, AttendanceCorrectionStatusUpdated::class);
});

test('attendance correction review rejects stale terminal status', function () {
    $admin = User::factory()->admin(true)->create();
    [, $user, , $employeeEmployee] = createAttendanceApprovalHierarchy();

    $correction = AttendanceCorrection::create([
        'employee_id' => $employeeEmployee->id,
        'user_id' => $user->id,
        'attendance_date' => now()->toDateString(),
        'request_type' => AttendanceCorrection::TYPE_WRONG_TIME,
        'requested_time_in' => Carbon::parse(now()->toDateString().' 08:15:00'),
        'reason' => 'Already rejected correction.',
        'status' => AttendanceCorrection::STATUS_REJECTED,
    ]);

    expect(fn () => app(AttendanceCorrectionService::class)->approve($correction, $admin))
        ->toThrow(AuthorizationException::class);

    expect($correction->fresh()->status)->toBe(AttendanceCorrection::STATUS_REJECTED);
});
