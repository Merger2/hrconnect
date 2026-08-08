<?php

use App\Livewire\User\AttendanceCorrectionPage;
use App\Models\Attendance;
use App\Models\AttendanceCorrection;
use App\Models\Division;
use App\Models\Employee;
use App\Models\Shift;
use App\Models\User;
use Livewire\Livewire;

/**
 * Acceptance — Modul 2: Absensi & Jadwal (PRD §Modul 2)
 *
 * Cakupan checklist (di-cover oleh Acceptance + suite detail):
 * - [ ] Clock-in/out geofence + face recognition → suite AttendanceServiceTest/AttendanceFaceEnforcementTest
 * - [ ] Mode production face-only tanpa PIN fallback → AttendanceFaceEnforcementTest
 * - [ ] Geofence 50m + toleransi 15 menit → AttendanceServiceTest
 * - [ ] Face gagal → ditolak + alur koreksi HR + audit trail → test ini (koreksi)
 * - [ ] Koreksi absensi dengan approval workflow + audit trail → test ini
 * - [ ] Dashboard ringkasan kehadiran HR/manager → OperationalReportsTest
 */
test('M2 acceptance: employee submits attendance correction → pending supervisor review', function () {
    $division = Division::factory()->create(['name' => 'Ops Acceptance']);
    $manager = User::factory()->create();
    $managerEmployee = Employee::factory()->create([
        'user_id' => $manager->id,
        'division_id' => $division->id,
    ]);

    $user = User::factory()->create(['manager_id' => $manager->id]);
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'parent_id' => $managerEmployee->id,
        'division_id' => $division->id,
    ]);

    $shift = Shift::factory()->create([
        'name' => 'Acceptance Shift',
        'start_time' => '09:00',
        'end_time' => '18:00',
    ]);

    // Tanpa clock_out → komponen mendeteksi missing check-out.
    $attendance = Attendance::factory()->create([
        'employee_id' => $employee->id,
        'date' => today(),
        'clock_in' => now()->setTime(9, 5),
        'clock_out' => null,
        'shift_id' => $shift->id,
    ]);

    $this->actingAs($user);

    // Pola komponen (AttendanceCorrectionFlowTest): create() → pilih tanggal → save().
    Livewire::test(AttendanceCorrectionPage::class)
        ->call('create')
        ->set('attendanceDate', now()->toDateString())
        ->set('includeRequestedTimeOut', true)
        ->set('requestedTimeOut', now()->toDateString().' 17:12')
        ->set('reason', 'Lupa clock-out saat pulang (acceptance test).')
        ->call('save')
        ->assertHasNoErrors();

    $correction = AttendanceCorrection::query()->first();
    expect($correction)->not->toBeNull()
        ->and($correction->user_id)->toBe($user->id)
        ->and($correction->employee_id)->toBe($employee->id)
        ->and($correction->request_type)->toBe(AttendanceCorrection::TYPE_MISSING_CHECK_OUT)
        ->and($correction->status)->toBe(AttendanceCorrection::STATUS_PENDING)
        ->and($correction->attendance_id)->toBe($attendance->id)
        ->and($correction->current_snapshot)->not->toBeNull();
});

test('M2 acceptance: correction submission requires a reason', function () {
    $user = User::factory()->create();
    Employee::factory()->create(['user_id' => $user->id]);
    $attendance = Attendance::factory()->create([
        'employee_id' => $user->employee->id,
        'date' => today(),
        'clock_in' => now()->setTime(9, 5),
    ]);

    $this->actingAs($user);

    Livewire::test(AttendanceCorrectionPage::class)
        ->call('create')
        ->set('attendanceDate', now()->toDateString())
        ->set('includeRequestedTimeOut', true)
        ->set('requestedTimeOut', now()->toDateString().' 17:12')
        ->set('reason', '')
        ->call('save')
        ->assertHasErrors(['reason']);
});
