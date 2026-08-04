<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\UploadedFile;

/**
 * Base date anchor — pakai tanggal 30 hari di masa lalu agar tidak terkena
 * Attendance::booted() saving hook yang me-reject future dates (perilaku
 * disengaja, lihat tests/Unit/Services/LeaveRequestServiceTest.php).
 */
$baseDate = now()->subDays(30);

function createLeaveValidationUser(): User
{
    $user = User::factory()->create();
    Employee::factory()->create(['user_id' => $user->id]);

    return $user;
}

function seedLeaveRequestSettings(): void
{
    Setting::updateOrCreate(
        ['key' => 'leave.require_attachment'],
        ['value' => '0', 'group' => 'leave', 'type' => 'boolean']
    );
    Setting::flushCache();
}

test('leave request is blocked when annual leave quota is exhausted', function () use ($baseDate) {
    seedLeaveRequestSettings();

    $user = createLeaveValidationUser();
    $annualLeave = LeaveType::create([
        'code' => 'annual_leave',
        'name' => 'Cuti Tahunan',
        'category' => LeaveType::CATEGORY_ANNUAL,
        'is_paid' => true,
        'deducts_from_quota' => true,
        'counts_against_quota' => true,
        'is_active' => true,
    ]);

    LeaveBalance::create([
        'employee_id' => $user->employee->id,
        'leave_type_id' => $annualLeave->id,
        'year' => $baseDate->year,
        'quota' => 1,
        'used' => 1,
        'carry_forward' => 0,
    ]);

    $date = $baseDate->toDateString();

    $response = $this->actingAs($user)->post(route('store-leave-request'), [
        'leave_type_id' => $annualLeave->id,
        'note' => 'Need another leave day',
        'from' => $date,
        'to' => $date,
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertSessionHas('error');

    expect(session('error'))->toContain('Saldo cuti tidak mencukupi');
});

test('sick leave request does not use annual quota', function () use ($baseDate) {
    seedLeaveRequestSettings();

    $user = createLeaveValidationUser();
    $sickLeave = LeaveType::create([
        'code' => 'sick_leave',
        'name' => 'Cuti Sakit',
        'category' => LeaveType::CATEGORY_SICK,
        'is_paid' => false,
        'deducts_from_quota' => false,
        'counts_against_quota' => false,
        'is_active' => true,
    ]);

    $date = $baseDate->toDateString();

    $response = $this->actingAs($user)->post(route('store-leave-request'), [
        'leave_type_id' => $sickLeave->id,
        'note' => 'Medical rest',
        'from' => $date,
        'to' => $date,
        'attachment' => UploadedFile::fake()->create('medical-certificate.pdf', 100, 'application/pdf'),
    ]);

    $response->assertRedirect(route('home'));

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $user->employee->id,
        'date' => $date,
        'status' => 'sick',
        'leave_type_id' => $sickLeave->id,
        'approval_status' => Attendance::STATUS_PENDING,
    ]);
});

test('custom leave type can be requested without annual quota usage', function () use ($baseDate) {
    seedLeaveRequestSettings();

    $user = createLeaveValidationUser();
    $customLeave = LeaveType::create([
        'code' => 'bereavement_leave',
        'name' => 'Cuti Duka',
        'category' => LeaveType::CATEGORY_OTHER,
        'is_paid' => true,
        'deducts_from_quota' => false,
        'counts_against_quota' => false,
        'is_active' => true,
    ]);

    $date = $baseDate->toDateString();

    $response = $this->actingAs($user)->post(route('store-leave-request'), [
        'leave_type_id' => $customLeave->id,
        'note' => 'Family bereavement',
        'from' => $date,
        'to' => $date,
    ]);

    $response->assertRedirect(route('home'));

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $user->employee->id,
        'date' => $date,
        'status' => 'excused',
        'leave_type_id' => $customLeave->id,
        'approval_status' => Attendance::STATUS_PENDING,
    ]);
});

test('leave apply page shows entitlement expiry information', function () {
    seedLeaveRequestSettings();

    $user = createLeaveValidationUser();
    $annualLeave = LeaveType::create([
        'code' => 'annual_leave',
        'name' => 'Cuti Tahunan',
        'category' => LeaveType::CATEGORY_ANNUAL,
        'is_paid' => true,
        'deducts_from_quota' => true,
        'counts_against_quota' => true,
        'is_active' => true,
    ]);

    $expiresAt = now()->endOfYear();
    LeaveBalance::create([
        'employee_id' => $user->employee->id,
        'leave_type_id' => $annualLeave->id,
        'year' => now()->year,
        'quota' => 12,
        'used' => 0,
        'carry_forward' => 0,
        'carry_forward_deadline' => $expiresAt,
    ]);

    $this->actingAs($user)
        ->get(route('apply-leave'))
        ->assertOk()
        ->assertSee(__('Valid until'))
        ->assertSee($expiresAt->translatedFormat('d M Y'));
});

test('leave request rejects unsafe attachment types and invalid coordinates', function () {
    seedLeaveRequestSettings();

    $user = createLeaveValidationUser();

    Setting::updateOrCreate(
        ['key' => 'leave.require_attachment'],
        ['value' => '1', 'group' => 'leave', 'type' => 'boolean']
    );
    Setting::flushCache();

    $date = now()->subDays(5)->toDateString();

    $response = $this->actingAs($user)->post(route('store-leave-request'), [
        'status' => 'sick',
        'note' => 'Need sick leave',
        'from' => $date,
        'to' => $date,
        'attachment' => UploadedFile::fake()->create('proof.exe', 1, 'application/x-msdownload'),
        'lat' => 91,
        'lng' => 181,
    ]);

    $response->assertSessionHasErrors(['attachment', 'lat', 'lng']);
});
