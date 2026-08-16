<?php

use App\Contracts\AttendanceServiceInterface;
use App\Domain\Attendance\ValueObjects\LeaveRequestResult;
use App\Enums\AttendanceStatus;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\Setting;
use App\Models\User;
use App\Services\Attendance\LeaveRequestService;
use App\Support\LeaveEntitlementService;
use App\Support\UserNotificationRecipientService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Base date anchor — semua tes menggunakan dates 30 hari di masa lalu
 * untuk menghindari Attendance::booted() saving hook yang reject future dates.
 */
beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $companyId = Company::create([
        'name' => 'PT Test',
        'code' => 'TST',
        'phone' => '021',
        'email' => 'test@test.com',
        'npwp' => '0',
        'is_active' => true,
    ])->id;

    $branchId = Branch::create([
        'company_id' => $companyId,
        'name' => 'HQ',
        'address' => 'JKT',
        'is_main' => true,
        'is_active' => true,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 500,
    ])->id;

    $deptId = DB::table('divisions')->insertGetId([
        'branch_id' => $branchId,
        'name' => 'Eng',
        'code' => 'ENG',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $posId = DB::table('positions')->insertGetId([
        'division_id' => $deptId,
        'name' => 'Staff',
        'code' => 'STF',
        'grade' => 1,
        'basic_salary' => 5_000_000,
        'allowance_jabatan' => 0,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->masterData = [
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'division_id' => $deptId,
        'position_id' => $posId,
    ];

    // Setup setting: leave attachment required = false (default)
    Setting::create([
        'key' => 'leave.require_attachment',
        'value' => '0',
        'group' => 'leave',
    ]);
    Setting::flushCache();

    // Create a default leave type
    $this->leaveType = LeaveType::create([
        'name' => 'Cuti Tahunan',
        'code' => 'CT',
        'quota' => 12,
        'is_paid' => true,
        'is_active' => true,
        'deducts_from_quota' => true,
        'eligible_for_carry_forward' => true,
    ]);

    // Create user + employee
    $this->user = createLeaveTestUser($this->masterData);

    // Notification fake — prevent actual emails/database notifications
    Notification::fake();

    // Base date for all tests — 30 days in the past
    $this->baseDate = Carbon::now()->subDays(30);

    $this->mockAttachmentService = Mockery::mock(AttendanceServiceInterface::class);
    $this->leaveEntitlements = new LeaveEntitlementService;
    $this->mockNotificationRecipients = Mockery::mock(UserNotificationRecipientService::class);

    $this->service = new LeaveRequestService(
        $this->mockAttachmentService,
        $this->leaveEntitlements,
        $this->mockNotificationRecipients,
    );
});

afterEach(function () {
    Mockery::close();
});

// ─── Helpers ───────────────────────────────────────────────────────────

function createLeaveTestUser(array $masterData): User
{
    $userId = DB::table('users')->insertGetId([
        'name' => 'Leave Tester',
        'email' => 'leave.tester@t.com',
        'password' => bcrypt('x'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Employee::create([
        'user_id' => $userId,
        'employee_number' => 'EMP-'.str()->random(6),
        'full_name' => 'Leave Tester',
        'phone' => '08123456789',
        'nik' => (string) rand(1000000000000000, 9999999999999999),
        'npwp' => (string) rand(100000, 999999),
        'bank_account_number' => (string) rand(1000000, 9999999),
        'bank_name' => 'BCA',
        'company_id' => $masterData['company_id'],
        'branch_id' => $masterData['branch_id'],
        'division_id' => $masterData['division_id'],
        'position_id' => $masterData['position_id'],
        'status' => 'active',
        'gender' => 'L',
        'marital_status' => 'single',
        'blood_type' => 'O+',
        'education_level' => 'sd',
        'institution_name' => 'Test U',
        'major' => 'CS',
        'graduation_year' => 2015,
        'birth_date' => '1990-01-01',
        'join_date' => '2025-01-01',
        'salary_type' => 'monthly',
    ]);

    return User::find($userId);
}

/**
 * Seed a LeaveBalance with sufficient quota for the test user.
 */
function seedLeaveQuota(User $user, LeaveType $leaveType, float $quota = 12, float $used = 0): LeaveBalance
{
    $employee = $user->employee;

    return LeaveBalance::create([
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'year' => now()->year,
        'quota' => $quota,
        'used' => $used,
        'carry_forward' => 0,
    ]);
}

// ═══════════════════════════════════════════════════════════════════════
// getApplyLeaveData()
// ═══════════════════════════════════════════════════════════════════════

test('getApplyLeaveData returns expected keys', function () {
    $data = $this->service->getApplyLeaveData($this->user);

    expect($data)->toHaveKeys([
        'attendance', 'annualQuota', 'usedExcused', 'remainingExcused',
        'annualLeaveExpiresAt', 'annualLeaveExpired',
        'leaveEntitlements', 'requireAttachment', 'leaveTypes',
    ]);
    expect($data['attendance'])->toBeNull();
    expect($data['leaveTypes'])->toBeCollection();
    expect($data['leaveTypes']->count())->toBe(1);
    expect($data['requireAttachment'])->toBeFalse();
});

test('getApplyLeaveData returns attendance when user has clocked in today', function () {
    Attendance::create([
        'employee_id' => $this->user->employee->id,
        'date' => now()->toDateString(),
        'clock_in' => now()->subHours(3),
        'status' => 'on_time',
    ]);

    $data = $this->service->getApplyLeaveData($this->user);

    expect($data['attendance'])->not->toBeNull();
    expect($data['attendance']->id)->toBeInt();
});

test('getApplyLeaveData reflects quota data from LeaveEntitlementService', function () {
    seedLeaveQuota($this->user, $this->leaveType, quota: 12, used: 2);

    $data = $this->service->getApplyLeaveData($this->user);

    expect($data['annualQuota'])->toBe(12);
    expect($data['usedExcused'])->toBe(2.0);
    expect($data['remainingExcused'])->toBe(10);
    expect($data['leaveEntitlements'])->toBeArray();
});

test('getApplyLeaveData returns empty leave types when none exist', function () {
    LeaveType::query()->delete();

    $data = $this->service->getApplyLeaveData($this->user);

    expect($data['leaveTypes'])->toBeCollection();
    expect($data['leaveTypes'])->toBeEmpty();
});

test('getApplyLeaveData returns requireAttachment=true when setting is 1', function () {
    Setting::where('key', 'leave.require_attachment')->update(['value' => '1']);
    Setting::flushCache();

    $data = $this->service->getApplyLeaveData($this->user);

    expect($data['requireAttachment'])->toBeTrue();
});

// ═══════════════════════════════════════════════════════════════════════
// submitLeaveRequest() — success paths
// Note: All dates use $this->baseDate (30 days in the past) to avoid
// Attendance::booted() saving hook yang me-reject future dates.
// ═══════════════════════════════════════════════════════════════════════

test('submitLeaveRequest succeeds for single date', function () {
    seedLeaveQuota($this->user, $this->leaveType);

    $this->mockNotificationRecipients
        ->shouldReceive('leaveApprovers')
        ->once()
        ->andReturn(collect());

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'excused',
        note: 'Ada urusan keluarga',
        fromDate: $this->baseDate,
        toDate: $this->baseDate,
        leaveType: null,
    );

    expect($result)->toBeInstanceOf(LeaveRequestResult::class);
    expect($result->ok)->toBeTrue();

    $attendance = Attendance::where('employee_id', $this->user->employee->id)
        ->whereDate('date', $this->baseDate->toDateString())
        ->first();
    expect($attendance)->not->toBeNull();
    expect($attendance->status)->toBe(AttendanceStatus::EXCUSED);
    expect($attendance->approval_status->value)->toBe('pending');
    expect($attendance->employee_id)->toBe($this->user->employee->id);
});

test('submitLeaveRequest succeeds for multiple dates', function () {
    seedLeaveQuota($this->user, $this->leaveType);

    $this->mockNotificationRecipients
        ->shouldReceive('leaveApprovers')
        ->once()
        ->andReturn(collect());

    $from = $this->baseDate;
    $to = $this->baseDate->copy()->addDays(2);

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'excused',
        note: 'Cuti 3 hari',
        fromDate: $from,
        toDate: $to,
    );

    expect($result->ok)->toBeTrue();

    $records = Attendance::where('employee_id', $this->user->employee->id)
        ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
        ->get();
    expect($records->count())->toBe(3);
    foreach ($records as $record) {
        expect($record->employee_id)->toBe($this->user->employee->id);
    }
});

test('submitLeaveRequest uses leaveType->attendanceStatus() to set status', function () {
    seedLeaveQuota($this->user, $this->leaveType);

    $this->mockNotificationRecipients
        ->shouldReceive('leaveApprovers')
        ->once()
        ->andReturn(collect());

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'sick',
        note: 'Cuti tahunan',
        fromDate: $this->baseDate,
        toDate: $this->baseDate,
        leaveType: $this->leaveType,
    );

    expect($result->ok)->toBeTrue();

    $attendance = Attendance::where('employee_id', $this->user->employee->id)
        ->whereDate('date', $this->baseDate->toDateString())
        ->first();
    expect($attendance->status)->toBe(AttendanceStatus::EXCUSED); // paid → excused
    expect($attendance->leave_type_id)->toBe($this->leaveType->id);
});

test('submitLeaveRequest uses sick status for unpaid leave type', function () {
    $unpaidType = LeaveType::create([
        'name' => 'Izin Sakit',
        'code' => 'S',
        'quota' => 0,
        'is_paid' => false,
        'is_active' => true,
        'deducts_from_quota' => false,
        'eligible_for_carry_forward' => false,
    ]);

    $this->mockNotificationRecipients
        ->shouldReceive('leaveApprovers')
        ->once()
        ->andReturn(collect());

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'excused',
        note: 'Sakit demam',
        fromDate: $this->baseDate,
        toDate: $this->baseDate,
        leaveType: $unpaidType,
    );

    expect($result->ok)->toBeTrue();

    $attendance = Attendance::where('employee_id', $this->user->employee->id)
        ->whereDate('date', $this->baseDate->toDateString())
        ->first();
    expect($attendance->status)->toBe(AttendanceStatus::SICK); // unpaid → sick
});

test('submitLeaveRequest stores attachment when provided', function () {
    seedLeaveQuota($this->user, $this->leaveType);

    $file = UploadedFile::fake()->create('document.pdf', 100);

    $this->mockAttachmentService
        ->shouldReceive('storeAttachment')
        ->once()
        ->with(Mockery::type(UploadedFile::class))
        ->andReturn('attachments/leave/document.pdf');

    $this->mockNotificationRecipients
        ->shouldReceive('leaveApprovers')
        ->once()
        ->andReturn(collect());

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'sick',
        note: 'Sakit demam',
        fromDate: $this->baseDate,
        toDate: $this->baseDate,
        attachment: $file,
    );

    expect($result->ok)->toBeTrue();
});

test('submitLeaveRequest with GPS coordinates sets lat_in and long_in', function () {
    seedLeaveQuota($this->user, $this->leaveType);

    $this->mockNotificationRecipients
        ->shouldReceive('leaveApprovers')
        ->once()
        ->andReturn(collect());

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'excused',
        note: 'Izin dari lokasi',
        fromDate: $this->baseDate,
        toDate: $this->baseDate,
        lat: -6.2088,
        lng: 106.8456,
    );

    expect($result->ok)->toBeTrue();

    $attendance = Attendance::where('employee_id', $this->user->employee->id)
        ->whereDate('date', $this->baseDate->toDateString())
        ->first();
    expect((float) $attendance->lat_in)->toEqual(-6.2088);
    expect((float) $attendance->long_in)->toEqual(106.8456);
});

test('submitLeaveRequest sends notification to approvers', function () {
    seedLeaveQuota($this->user, $this->leaveType);

    $this->mockNotificationRecipients
        ->shouldReceive('leaveApprovers')
        ->once()
        ->andReturn(collect([$this->user]));

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'excused',
        note: 'Izin dengan notifikasi',
        fromDate: $this->baseDate,
        toDate: $this->baseDate,
    );

    expect($result->ok)->toBeTrue();
});

// ═══════════════════════════════════════════════════════════════════════
// submitLeaveRequest() — quota error paths
// ═══════════════════════════════════════════════════════════════════════

test('submitLeaveRequest returns error when quota insufficient', function () {
    seedLeaveQuota($this->user, $this->leaveType, quota: 12, used: 12);

    $from = $this->baseDate;
    $to = $this->baseDate->copy()->addDays(2);

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'excused',
        note: 'Cuti panjang',
        fromDate: $from,
        toDate: $to,
        leaveType: $this->leaveType,
    );

    expect($result->ok)->toBeFalse();
    expect($result->error)->toContain('Saldo cuti tidak mencukupi');
});

test('submitLeaveRequest returns error when no quota balance exists', function () {
    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'excused',
        note: 'Cuti tanpa saldo',
        fromDate: $this->baseDate,
        toDate: $this->baseDate,
        leaveType: $this->leaveType,
    );

    expect($result->ok)->toBeFalse();
    expect($result->error)->toContain('Tidak ditemukan saldo cuti');
});

test('submitLeaveRequest skips quota check when leaveType does not deduct from quota', function () {
    $sickType = LeaveType::create([
        'name' => 'Izin Sakit',
        'code' => 'S',
        'quota' => 0,
        'is_paid' => false,
        'is_active' => true,
        'deducts_from_quota' => false,
        'eligible_for_carry_forward' => false,
    ]);

    $this->mockNotificationRecipients
        ->shouldReceive('leaveApprovers')
        ->once()
        ->andReturn(collect());

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'sick',
        note: 'Sakit kepala',
        fromDate: $this->baseDate,
        toDate: $this->baseDate,
        leaveType: $sickType,
    );

    expect($result->ok)->toBeTrue();
});

// ═══════════════════════════════════════════════════════════════════════
// submitLeaveRequest() — existing records conflicts
// ═══════════════════════════════════════════════════════════════════════

test('submitLeaveRequest returns error when user already clocked in/out on requested date', function () {
    seedLeaveQuota($this->user, $this->leaveType);

    Attendance::create([
        'employee_id' => $this->user->employee->id,
        'date' => $this->baseDate->toDateString(),
        'clock_in' => $this->baseDate->copy()->setHour(8),
        'clock_out' => $this->baseDate->copy()->setHour(17),
        'status' => 'on_time',
    ]);

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'excused',
        note: 'Izin setelah absen',
        fromDate: $this->baseDate,
        toDate: $this->baseDate,
    );

    expect($result->ok)->toBeFalse();
    expect($result->error)->toContain('sudah melakukan absensi');
});

test('submitLeaveRequest returns error when user already has pending leave on requested date', function () {
    seedLeaveQuota($this->user, $this->leaveType);

    Attendance::create([
        'employee_id' => $this->user->employee->id,
        'date' => $this->baseDate->toDateString(),
        'status' => 'excused',
        'approval_status' => 'pending',
    ]);

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'excused',
        note: 'Cuti double',
        fromDate: $this->baseDate,
        toDate: $this->baseDate,
    );

    expect($result->ok)->toBeFalse();
    expect($result->error)->toContain('sudah memiliki pengajuan izin');
});

test('submitLeaveRequest returns error when user already has approved leave on requested date', function () {
    seedLeaveQuota($this->user, $this->leaveType);

    Attendance::create([
        'employee_id' => $this->user->employee->id,
        'date' => $this->baseDate->toDateString(),
        'status' => 'excused',
        'approval_status' => 'approved',
    ]);

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'excused',
        note: 'Cuti sudah disetujui',
        fromDate: $this->baseDate,
        toDate: $this->baseDate,
    );

    expect($result->ok)->toBeFalse();
    expect($result->error)->toContain('sudah memiliki pengajuan izin');
});

// ═══════════════════════════════════════════════════════════════════════
// submitLeaveRequest() — edge cases
// ═══════════════════════════════════════════════════════════════════════

test('submitLeaveRequest updates existing attendance if clock_in and clock_out are both null', function () {
    seedLeaveQuota($this->user, $this->leaveType);

    $this->mockNotificationRecipients
        ->shouldReceive('leaveApprovers')
        ->once()
        ->andReturn(collect());

    Attendance::create([
        'employee_id' => $this->user->employee->id,
        'date' => $this->baseDate->toDateString(),
        'status' => 'on_time',
    ]);

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'excused',
        note: 'Update status existing',
        fromDate: $this->baseDate,
        toDate: $this->baseDate,
        leaveType: $this->leaveType,
    );

    expect($result->ok)->toBeTrue();

    $attendance = Attendance::where('employee_id', $this->user->employee->id)
        ->whereDate('date', $this->baseDate->toDateString())
        ->first();
    expect($attendance->status)->toBe(AttendanceStatus::EXCUSED);
    expect($attendance->approval_status->value)->toBe('pending');
});

test('submitLeaveRequest does not create duplicate records when only some dates have conflicts', function () {
    seedLeaveQuota($this->user, $this->leaveType);

    $from = $this->baseDate;
    $to = $this->baseDate->copy()->addDays(2);
    $middleDate = $this->baseDate->copy()->addDay();

    Attendance::create([
        'employee_id' => $this->user->employee->id,
        'date' => $middleDate->toDateString(),
        'clock_in' => $middleDate->copy()->setHour(8),
        'status' => 'on_time',
    ]);

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'excused',
        note: 'Multi-day conflict',
        fromDate: $from,
        toDate: $to,
    );

    expect($result->ok)->toBeFalse();
    expect($result->error)->toContain('sudah melakukan absensi');
});

test('submitLeaveRequest handles non-deducting leave type without quota', function () {
    $sickType = LeaveType::create([
        'name' => 'Izin',
        'code' => 'I',
        'quota' => 0,
        'is_paid' => false,
        'is_active' => true,
        'deducts_from_quota' => false,
        'eligible_for_carry_forward' => false,
    ]);

    $this->mockNotificationRecipients
        ->shouldReceive('leaveApprovers')
        ->once()
        ->andReturn(collect());

    $result = $this->service->submitLeaveRequest(
        user: $this->user,
        status: 'sick',
        note: 'Tidak enak badan',
        fromDate: $this->baseDate,
        toDate: $this->baseDate,
        leaveType: $sickType,
    );

    expect($result->ok)->toBeTrue();
});
