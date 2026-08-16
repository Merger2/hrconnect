<?php

use App\Domain\Attendance\AttendanceRiskScorer;
use App\Enums\ApprovalStatus;
use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\VerificationMethod;
use App\Exceptions\AlreadyClockedInException;
use App\Exceptions\AntiFakeGPSException;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRegisteredException;
use App\Exceptions\NotClockedInException;
use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\FaceDescriptor;
use App\Services\Attendance\AttendanceService;
use App\Services\Attendance\GeofenceService;
use App\Services\Security\FaceRecognitionService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Master data setup — mengikuti pola FaceRecognitionServiceTest.
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

    // Create a default shift (00:00 start) so clock-in tests are never "late"
    // regardless of when the test runs.
    $shiftId = DB::table('shifts')->insertGetId([
        'name' => 'Regular',
        'start_time' => '23:00:00',
        'end_time' => '23:59:00',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->masterData = [
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'division_id' => $deptId,
        'position_id' => $posId,
        'shift_id' => $shiftId,
    ];

    // Instantiate services once per test suite (stateless)
    $this->geofenceService = new GeofenceService;
    $this->faceRecognitionService = new FaceRecognitionService;
    $this->riskScorer = new AttendanceRiskScorer;
    $this->attendanceService = new AttendanceService(
        $this->geofenceService,
        $this->faceRecognitionService,
        $this->riskScorer,
    );
});

// ─── Helpers ───────────────────────────────────────────────────────────

function attSvcMakeEmployee(array $masterData, string $name = 'Attendance Emp'): Employee
{
    $userId = DB::table('users')->insertGetId([
        'name' => $name,
        'email' => strtolower(str_replace(' ', '.', $name)).'@t.com',
        'password' => bcrypt('x'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return Employee::create([
        'user_id' => $userId,
        'employee_number' => 'EMP-'.str()->random(6),
        'full_name' => $name,
        'phone' => '08'.rand(10000000, 99999999),
        'nik' => (string) rand(1000000000000000, 9999999999999999),
        'npwp' => (string) rand(100000, 999999),
        'bank_account_number' => (string) rand(1000000, 9999999),
        'bank_name' => 'BCA',
        'company_id' => $masterData['company_id'],
        'branch_id' => $masterData['branch_id'],
        'division_id' => $masterData['division_id'],
        'position_id' => $masterData['position_id'],
        'shift_id' => $masterData['shift_id'],
        'status' => EmployeeStatus::ACTIVE,
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
}

/**
 * Set employee PIN — via raw DB update to avoid CipherSweet re-encryption
 * on full model save (which would exceed varchar(60) for encrypted fields).
 * Uses Hash::make() which defers to the app default hasher (argon2id).
 */
function attSvcSetPin(Employee $emp, string $pin = '123456'): void
{
    DB::table('employees')->where('id', $emp->id)->update([
        'pin' => Hash::make($pin),
    ]);
    $emp->refresh();
}

/**
 * Enroll face descriptor for an employee (128D vector of 0.1).
 */
function attSvcEnrollFace(Employee $emp): void
{
    FaceDescriptor::create([
        'employee_id' => $emp->id,
        'embedding' => '['.implode(',', array_fill(0, 128, 0.1)).']',
        'is_active' => true,
    ]);
}

/**
 * Create a clock-in attendance record for today.
 */
function attSvcCreateClockIn(Employee $emp, array $overrides = []): Attendance
{
    return Attendance::create(array_merge([
        'employee_id' => $emp->id,
        'shift_id' => $emp->shift_id,
        'date' => now()->toDateString(),
        'clock_in' => now()->subHours(2),
        'status' => AttendanceStatus::ON_TIME,
    ], $overrides));
}

/**
 * Valid GPS data within branch radius.
 */
function attSvcValidGps(): array
{
    return [
        'latitude' => -6.2,
        'longitude' => 106.8,
        'accuracy' => 10,
        'gps_variance' => 0.001,
    ];
}

/**
 * A 128D embedding vector (all 0.1) for face tests.
 */
function attSvcFaceEmbedding(): array
{
    return array_fill(0, 128, 0.1);
}

// ═══════════════════════════════════════════════════════════════════════
// clockIn()
// ═══════════════════════════════════════════════════════════════════════

// ─── Pre-conditions ───────────────────────────────────────────────────

test('clockIn throws AlreadyClockedInException when already clocked in today', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcCreateClockIn($emp);

    expect(fn () => $this->attendanceService->clockIn($emp, attSvcValidGps()))
        ->toThrow(AlreadyClockedInException::class, 'sudah melakukan absensi masuk');
});

test('clockIn throws AntiFakeGPSException when is_mocked is true', function () {
    $emp = attSvcMakeEmployee($this->masterData);

    expect(fn () => $this->attendanceService->clockIn($emp, [
        'latitude' => -6.2,
        'longitude' => 106.8,
        'is_mocked' => true,
    ]))->toThrow(AntiFakeGPSException::class, 'Fake GPS');
});

// SKIP — employees.branch_id kolom NOT NULL (FK constraint), sehingga
// tidak bisa mensimulasikan employee tanpa branch via DB.
// GeofenceServiceTest sudah cover skenario branch tanpa radius/koordinat.

// ─── Verification failures ────────────────────────────────────────────

test('clockIn throws BusinessRuleException when face not enrolled and no PIN', function () {
    $emp = attSvcMakeEmployee($this->masterData);

    // Employee has branch, shift — but no face descriptor, and no pin/face_embedding in data
    expect(fn () => $this->attendanceService->clockIn($emp, attSvcValidGps()))
        ->toThrow(BusinessRuleException::class, 'Wajah Anda belum terdaftar');
});

test('clockIn throws BusinessRuleException when PIN is sent but wrong (PIN ignored, face-only)', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcSetPin($emp, '123456');

    // Face not enrolled → PIN tidak lagi diproses (face-only policy, PRD §1/§4)
    expect(fn () => $this->attendanceService->clockIn($emp, array_merge(attSvcValidGps(), [
        'pin' => 'wrong-pin',
    ])))->toThrow(BusinessRuleException::class, 'Wajah Anda belum terdaftar');
});

// ─── WFA validation ───────────────────────────────────────────────────

test('clockIn throws BusinessRuleException when WFA note is too short', function () {
    $emp = attSvcMakeEmployee($this->masterData);

    expect(fn () => $this->attendanceService->clockIn($emp, [
        'is_wfa' => true,
        'wfa_note' => 'short', // < 20 chars
    ]))->toThrow(BusinessRuleException::class, 'minimal 20 karakter');
});

// ─── Success: Face Verification ───────────────────────────────────────

test('clockIn succeeds with face verification', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcEnrollFace($emp);

    $attendance = $this->attendanceService->clockIn($emp, array_merge(attSvcValidGps(), [
        'face_embedding' => attSvcFaceEmbedding(),
    ]));

    expect($attendance)->toBeInstanceOf(Attendance::class);
    expect($attendance->employee_id)->toBe($emp->id);
    expect($attendance->verification_method)->toBe(VerificationMethod::FACE_VERIFIED);
    expect($attendance->face_similarity_score)->toBeGreaterThanOrEqual(99.0);
    expect($attendance->clock_in)->not->toBeNull();
    expect($attendance->is_wfa)->toBeFalse();
    expect($attendance->status)->toBe(AttendanceStatus::ON_TIME);
    expect($attendance->lat_in)->toEqual(-6.2);
    expect($attendance->long_in)->toEqual(106.8);
    // Should have risk score
    expect($attendance->risk_score)->not->toBeNull();
});

// ─── Face-only: PIN tidak lagi menjadi fallback ───────────────────────

test('clockIn throws BusinessRuleException when face not enrolled even with valid PIN', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcSetPin($emp, '123456');

    // Face-only: PIN tidak menyelamatkan; wajah wajib terdaftar
    expect(fn () => $this->attendanceService->clockIn($emp, array_merge(attSvcValidGps(), [
        'pin' => '123456',
    ])))->toThrow(BusinessRuleException::class, 'Wajah Anda belum terdaftar');
});

// ─── Success: Face fails → tolak (bukan PIN fallback) ─────────────────

test('clockIn throws BusinessRuleException when face does not match', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcEnrollFace($emp);
    attSvcSetPin($emp, '123456');

    // Face enrolled, but send an opposite-direction embedding that will NOT match
    $different = [];
    for ($i = 0; $i < 128; $i++) {
        $different[] = $i % 2 === 0 ? 1.0 : -1.0;
    }

    // PIN ikut dikirim tapi diabaikan — face gagal = tolak
    expect(fn () => $this->attendanceService->clockIn($emp, array_merge(attSvcValidGps(), [
        'face_embedding' => $different,
        'pin' => '123456',
    ])))->toThrow(BusinessRuleException::class, 'Verifikasi wajah gagal');
});

// ─── Success: WFA ─────────────────────────────────────────────────────

test('clockIn succeeds with WFA', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcEnrollFace($emp);

    $attendance = $this->attendanceService->clockIn($emp, [
        'is_wfa' => true,
        'wfa_note' => 'Bekerja dari rumah karena ada perbaikan AC di kantor',
        'face_embedding' => attSvcFaceEmbedding(),
    ]);

    expect($attendance)->toBeInstanceOf(Attendance::class);
    expect($attendance->is_wfa)->toBeTrue();
    expect($attendance->status_wfa)->toBe(ApprovalStatus::PENDING);
    expect($attendance->wfa_note)->toBe('Bekerja dari rumah karena ada perbaikan AC di kantor');
    // WFA should not have late_minutes penalty (B-34)
    expect($attendance->late_minutes)->toBe(0);
    // Geofence NOT validated for WFA — no GPS data needed
    expect($attendance->lat_in)->toBeNull();
});

// ═══════════════════════════════════════════════════════════════════════
// clockOut()
// ═══════════════════════════════════════════════════════════════════════

// ─── Pre-conditions ───────────────────────────────────────────────────

test('clockOut throws NotClockedInException when no active attendance', function () {
    $emp = attSvcMakeEmployee($this->masterData);

    expect(fn () => $this->attendanceService->clockOut($emp, attSvcValidGps()))
        ->toThrow(NotClockedInException::class, 'Tidak ada absensi masuk');
});

test('clockOut throws AntiFakeGPSException when is_mocked is true', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcCreateClockIn($emp);

    expect(fn () => $this->attendanceService->clockOut($emp, [
        'is_mocked' => true,
    ]))->toThrow(AntiFakeGPSException::class, 'Fake GPS');
});

// SKIP — same reason as clockIn: employees.branch_id is NOT NULL.

// ─── Success: Face Verification ───────────────────────────────────────

test('clockOut succeeds with face verification', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcEnrollFace($emp);
    attSvcCreateClockIn($emp);

    $attendance = $this->attendanceService->clockOut($emp, array_merge(attSvcValidGps(), [
        'face_embedding' => attSvcFaceEmbedding(),
    ]));

    expect($attendance)->toBeInstanceOf(Attendance::class);
    expect($attendance->employee_id)->toBe($emp->id);
    expect($attendance->clock_out)->not->toBeNull();
    expect($attendance->clock_out_verification_method)->toBe(VerificationMethod::FACE_VERIFIED);
    expect($attendance->clock_out_face_similarity_score)->toBeGreaterThanOrEqual(99.0);
    expect($attendance->photo_selfie_out)->toBeNull();
    expect($attendance->lat_out)->toEqual(-6.2);
    expect($attendance->long_out)->toEqual(106.8);
});

// ─── Face-only: PIN tidak lagi menjadi fallback ───────────────────────

test('clockOut throws BusinessRuleException when face not enrolled even with valid PIN', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcSetPin($emp, '123456');
    attSvcCreateClockIn($emp);

    expect(fn () => $this->attendanceService->clockOut($emp, array_merge(attSvcValidGps(), [
        'pin' => '123456',
    ])))->toThrow(BusinessRuleException::class, 'Wajah Anda belum terdaftar');
});

// ─── Success: WFA clock-out skips geofence ────────────────────────────

test('clockOut succeeds with WFA clock-out skipping geofence', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcEnrollFace($emp);
    attSvcCreateClockIn($emp, ['is_wfa' => true]);

    // No GPS data needed — WFA skips geofence
    $attendance = $this->attendanceService->clockOut($emp, [
        'face_embedding' => attSvcFaceEmbedding(),
    ]);

    expect($attendance->is_wfa)->toBeTrue();
    expect($attendance->clock_out)->not->toBeNull();
});

// ═══════════════════════════════════════════════════════════════════════
// Edge cases & integration behavior
// ═══════════════════════════════════════════════════════════════════════

test('clockIn twice in rapid succession handles unique constraint gracefully', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcEnrollFace($emp);

    // First clock-in succeeds
    $this->attendanceService->clockIn($emp, array_merge(attSvcValidGps(), [
        'face_embedding' => attSvcFaceEmbedding(),
    ]));

    // Second clock-in should throw AlreadyClockedInException
    expect(fn () => $this->attendanceService->clockIn($emp, array_merge(attSvcValidGps(), [
        'face_embedding' => attSvcFaceEmbedding(),
    ])))->toThrow(AlreadyClockedInException::class);
});

test('clockIn with face enrolled but face fails and no PIN throws', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcEnrollFace($emp);

    // Face enrolled, send different face embedding (won't match), but NO PIN
    $different = [];
    for ($i = 0; $i < 128; $i++) {
        $different[] = $i % 2 === 0 ? 1.0 : -1.0;
    }

    expect(fn () => $this->attendanceService->clockIn($emp, array_merge(attSvcValidGps(), [
        'face_embedding' => $different,
        // no pin
    ])))->toThrow(BusinessRuleException::class, 'Verifikasi wajah gagal');
});

test('clockOut throws BusinessRuleException when face not enrolled and no PIN', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcCreateClockIn($emp);

    expect(fn () => $this->attendanceService->clockOut($emp, attSvcValidGps()))
        ->toThrow(BusinessRuleException::class, 'Wajah Anda belum terdaftar');
});

test('clockIn stores photo_selfie when provided', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcEnrollFace($emp);

    $attendance = $this->attendanceService->clockIn($emp, array_merge(attSvcValidGps(), [
        'face_embedding' => attSvcFaceEmbedding(),
        'photo_selfie' => 'data:image/jpeg;base64,/9j/4AAQ...',
    ]));

    expect($attendance->photo_selfie_in)->toBe('data:image/jpeg;base64,/9j/4AAQ...');
});

test('clockOut stores photo_selfie when provided', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcEnrollFace($emp);
    attSvcCreateClockIn($emp);

    $attendance = $this->attendanceService->clockOut($emp, array_merge(attSvcValidGps(), [
        'face_embedding' => attSvcFaceEmbedding(),
        'photo_selfie' => 'data:image/jpeg;base64,/9j/4AAQ...out',
    ]));

    expect($attendance->photo_selfie_out)->toBe('data:image/jpeg;base64,/9j/4AAQ...out');
});

// ═══════════════════════════════════════════════════════════════════════
// Race condition: descriptor deleted between hasFaceEnrolled and verifyFace
// ═══════════════════════════════════════════════════════════════════════

test('clockIn throws BusinessRuleException when descriptor is deleted between hasFaceEnrolled and verifyFace (race condition)', function () {
    $emp = attSvcMakeEmployee($this->masterData);

    // Mock FaceRecognitionService to simulate race condition:
    //   hasFaceEnrolled → true  (descriptor exists at check time)
    //   verifyFace → FaceNotRegisteredException (descriptor deleted before verification)
    $mockFaceService = $this->createMock(FaceRecognitionService::class);
    $mockFaceService->method('hasFaceEnrolled')->willReturn(true);
    $mockFaceService->method('verifyFace')
        ->willThrowException(new FaceNotRegisteredException('Face descriptor not found'));

    $attendanceService = new AttendanceService(
        $this->geofenceService,
        $mockFaceService,
        $this->riskScorer,
    );

    // Face-only: face hilang = tolak (bukan PIN fallback)
    expect(fn () => $attendanceService->clockIn($emp, array_merge(attSvcValidGps(), [
        'face_embedding' => attSvcFaceEmbedding(),
    ])))->toThrow(BusinessRuleException::class, 'Verifikasi wajah gagal');
});

test('clockIn throws BusinessRuleException when descriptor deleted between hasFaceEnrolled and verifyFace and no PIN', function () {
    $emp = attSvcMakeEmployee($this->masterData);

    $mockFaceService = $this->createMock(FaceRecognitionService::class);
    $mockFaceService->method('hasFaceEnrolled')->willReturn(true);
    $mockFaceService->method('verifyFace')
        ->willThrowException(new FaceNotRegisteredException('Face descriptor not found'));

    $attendanceService = new AttendanceService(
        $this->geofenceService,
        $mockFaceService,
        $this->riskScorer,
    );

    expect(fn () => $attendanceService->clockIn($emp, array_merge(attSvcValidGps(), [
        'face_embedding' => attSvcFaceEmbedding(),
        // no pin
    ])))->toThrow(BusinessRuleException::class, 'Verifikasi wajah gagal');
});

test('clockOut throws BusinessRuleException when descriptor is deleted between hasFaceEnrolled and verifyFace (race condition)', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcCreateClockIn($emp);

    $mockFaceService = $this->createMock(FaceRecognitionService::class);
    $mockFaceService->method('hasFaceEnrolled')->willReturn(true);
    $mockFaceService->method('verifyFace')
        ->willThrowException(new FaceNotRegisteredException('Face descriptor not found'));

    $attendanceService = new AttendanceService(
        $this->geofenceService,
        $mockFaceService,
        $this->riskScorer,
    );

    // Face-only: face hilang = tolak (bukan PIN fallback)
    expect(fn () => $attendanceService->clockOut($emp, array_merge(attSvcValidGps(), [
        'face_embedding' => attSvcFaceEmbedding(),
    ])))->toThrow(BusinessRuleException::class, 'Verifikasi wajah gagal');
});

test('clockOut throws BusinessRuleException when descriptor deleted between hasFaceEnrolled and verifyFace and no PIN', function () {
    $emp = attSvcMakeEmployee($this->masterData);
    attSvcCreateClockIn($emp);

    $mockFaceService = $this->createMock(FaceRecognitionService::class);
    $mockFaceService->method('hasFaceEnrolled')->willReturn(true);
    $mockFaceService->method('verifyFace')
        ->willThrowException(new FaceNotRegisteredException('Face descriptor not found'));

    $attendanceService = new AttendanceService(
        $this->geofenceService,
        $mockFaceService,
        $this->riskScorer,
    );

    expect(fn () => $attendanceService->clockOut($emp, array_merge(attSvcValidGps(), [
        'face_embedding' => attSvcFaceEmbedding(),
        // no pin
    ])))->toThrow(BusinessRuleException::class, 'Verifikasi wajah gagal');
});
