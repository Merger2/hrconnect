<?php

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\RequestStatus;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Leave;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Tests untuk 5 console commands (Sesi 6).
 *
 * Strategi: pakai DB::table bypass untuk bikin master data minimal
 * (Company/Branch/Department/Position) sesuai pattern EmployeeSeeder.
 * Hindari Employee factory chain yang berat.
 */

beforeEach(function () {
    $this->seed(\Database\Seeders\RoleAndPermissionSeeder::class);

    // Master data minimal untuk Employee FK
    $companyId = DB::table('companies')->insertGetId([
        'name' => 'PT Test',
        'code' => 'TST',
        'phone' => '021',
        'email' => 'test@test.com',
        'npwp' => '0',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $branchId = DB::table('branches')->insertGetId([
        'company_id' => $companyId,
        'name' => 'HQ',
        'address' => 'JKT',
        'is_main' => true,
        'is_active' => true,
        'latitude' => -6.2,
        'longitude' => 106.8,
        'radius' => 100,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $deptId = DB::table('departments')->insertGetId([
        'branch_id' => $branchId,
        'name' => 'Eng',
        'code' => 'ENG',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $posId = DB::table('positions')->insertGetId([
        'department_id' => $deptId,
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
        'department_id' => $deptId,
        'position_id' => $posId,
    ];
});

function makeActiveEmployee(array $masterData, string $name = 'Test Emp'): Employee
{
    $userId = DB::table('users')->insertGetId([
        'name' => $name,
        'email' => $name.'@t.com',
        'password' => bcrypt('x'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Pakai Eloquent model supaya CipherSweet jalan untuk encrypted columns.
    $employee = Employee::create([
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
        'department_id' => $masterData['department_id'],
        'position_id' => $masterData['position_id'],
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

    return $employee;
}

// ─── attendance:detect-alpha ──────────────────────────────────────────

test('detect-alpha skip kalau target tanggal weekend', function () {
    $saturday = Carbon::create(2026, 6, 6); // Sabtu

    Artisan::call('attendance:detect-alpha', ['--date' => $saturday->toDateString()]);

    expect(Attendance::count())->toBe(0);
});

test('detect-alpha skip kalau target tanggal holiday', function () {
    $tuesday = Carbon::create(2026, 5, 19); // Selasa
    Holiday::create([
        'date' => $tuesday->toDateString(),
        'name' => 'Test Holiday',
        'is_active' => true,
    ]);

    makeActiveEmployee($this->masterData);

    Artisan::call('attendance:detect-alpha', ['--date' => $tuesday->toDateString()]);

    expect(Attendance::count())->toBe(0);
});

test('detect-alpha buat record absent untuk karyawan tanpa attendance & tanpa cuti', function () {
    $monday = Carbon::create(2026, 5, 18); // Senin
    $emp = makeActiveEmployee($this->masterData, 'NoShowEmp');

    Artisan::call('attendance:detect-alpha', ['--date' => $monday->toDateString()]);

    $att = Attendance::where('employee_id', $emp->id)->first();
    expect($att)->not->toBeNull();
    expect($att->status)->toBe(AttendanceStatus::ABSENT);
});

test('detect-alpha skip karyawan yang sudah punya attendance record', function () {
    $monday = Carbon::create(2026, 5, 18);
    $emp = makeActiveEmployee($this->masterData, 'AlreadyClockedIn');

    Attendance::create([
        'employee_id' => $emp->id,
        'date' => $monday->toDateString(),
        'clock_in' => $monday->copy()->setTime(8, 0),
        'status' => AttendanceStatus::ON_TIME,
        'verification_method' => 'face_verified',
    ]);

    Artisan::call('attendance:detect-alpha', ['--date' => $monday->toDateString()]);

    expect(Attendance::where('employee_id', $emp->id)->count())->toBe(1);
});

test('detect-alpha skip karyawan yang punya cuti approved', function () {
    $monday = Carbon::create(2026, 5, 18);
    $emp = makeActiveEmployee($this->masterData, 'OnLeave');

    $leaveType = LeaveType::create([
        'name' => 'Annual',
        'code' => 'annual',
        'quota' => 12,
        'deducts_from_quota' => true,
        'is_paid' => true,
        'is_active' => true,
    ]);

    Leave::create([
        'employee_id' => $emp->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => $monday->toDateString(),
        'end_date' => $monday->toDateString(),
        'day_type' => 'full_day',
        'total_days' => 1,
        'reason' => 'Test',
        'status' => RequestStatus::APPROVED,
    ]);

    Artisan::call('attendance:detect-alpha', ['--date' => $monday->toDateString()]);

    expect(Attendance::where('employee_id', $emp->id)->count())->toBe(0);
});

// ─── attendance:detect-chronic-late ───────────────────────────────────

test('detect-chronic-late jalan tanpa error walau tidak ada karyawan', function () {
    $exitCode = Artisan::call('attendance:detect-chronic-late');

    expect($exitCode)->toBe(0);
});

test('detect-chronic-late identify karyawan dengan 3+ late di bulan target', function () {
    $emp = makeActiveEmployee($this->masterData, 'ChronicLate');

    // Bikin 3 attendance late
    foreach ([5, 10, 15] as $day) {
        Attendance::create([
            'employee_id' => $emp->id,
            'date' => Carbon::create(2026, 5, $day)->toDateString(),
            'late_minutes' => 30,
            'status' => AttendanceStatus::LATE,
            'verification_method' => 'face_verified',
        ]);
    }

    $exitCode = Artisan::call('attendance:detect-chronic-late', ['--month' => '2026-05']);
    $output = Artisan::output();

    expect($exitCode)->toBe(0);
    expect($output)->toContain('ChronicLate');
    expect($output)->toContain('3x telat');
});

// ─── leave:reset-quota ────────────────────────────────────────────────

test('leave:reset-quota berhasil tanpa karyawan aktif', function () {
    $exitCode = Artisan::call('leave:reset-quota');

    expect($exitCode)->toBe(0);
});

test('leave:reset-quota initialize balance untuk karyawan aktif', function () {
    LeaveType::create([
        'name' => 'Annual',
        'code' => 'annual',
        'quota' => 12,
        'deducts_from_quota' => true,
        'is_paid' => true,
        'is_active' => true,
    ]);

    $emp = makeActiveEmployee($this->masterData, 'NewYearReset');

    $exitCode = Artisan::call('leave:reset-quota', [
        '--from-year' => 2025,
        '--to-year' => 2026,
    ]);

    expect($exitCode)->toBe(0);
    expect(\App\Models\LeaveBalance::where('employee_id', $emp->id)->where('year', 2026)->exists())->toBeTrue();
});

// ─── payroll:generate ─────────────────────────────────────────────────

test('payroll:generate dispatch job per karyawan aktif', function () {
    \Illuminate\Support\Facades\Queue::fake();

    makeActiveEmployee($this->masterData, 'PayrollEmp1');
    makeActiveEmployee($this->masterData, 'PayrollEmp2');

    Artisan::call('payroll:generate', ['--period' => '2026-05']);

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\GenerateEmployeePayrollJob::class, 2);
});

test('payroll:generate single employee mode', function () {
    \Illuminate\Support\Facades\Queue::fake();

    $emp1 = makeActiveEmployee($this->masterData, 'PayrollEmp1');
    makeActiveEmployee($this->masterData, 'PayrollEmp2');

    Artisan::call('payroll:generate', [
        '--period' => '2026-05',
        '--employee' => $emp1->id,
    ]);

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\GenerateEmployeePayrollJob::class, 1);
});

test('payroll:generate gagal kalau format periode salah', function () {
    $exitCode = Artisan::call('payroll:generate', ['--period' => 'invalid']);

    expect($exitCode)->toBe(1);
});

// ─── cache:warm ───────────────────────────────────────────────────────

test('cache:warm jalan tanpa error', function () {
    $exitCode = Artisan::call('cache:warm');

    expect($exitCode)->toBe(0);

    // Cache populated
    expect(\Illuminate\Support\Facades\Cache::has('tax_configs'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Cache::has('bpjs_configs'))->toBeTrue();
});

test('cache:warm dengan opsi year', function () {
    $exitCode = Artisan::call('cache:warm', ['--year' => 2026]);

    expect($exitCode)->toBe(0);
    expect(\Illuminate\Support\Facades\Cache::has('holidays:2026'))->toBeTrue();
});
