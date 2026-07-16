<?php

use App\Enums\EmployeeStatus;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRegisteredException;
use App\Models\Employee;
use App\Models\FaceDescriptor;
use App\Services\FaceRecognitionService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * Master data minimal (Company/Branch/Department/Position) untuk memenuhi
 * FK constraint Employee. Pattern mengikuti ConsoleCommandsTest.
 */
beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

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

function makeFaceTestEmployee(array $masterData, string $name = 'Face Test Emp'): Employee
{
    $userId = DB::table('users')->insertGetId([
        'name' => $name,
        'email' => $name.'@t.com',
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
}

// ─── Dimension ─────────────────────────────────────────────────────────

test('getEmbeddingDimension returns 128', function () {
    $svc = new FaceRecognitionService;

    expect($svc->getEmbeddingDimension())->toBe(128);
});

// ─── No Face Registered ────────────────────────────────────────────────

test('throws FaceNotRegisteredException when employee has no face descriptor', function () {
    $svc = new FaceRecognitionService;
    $emp = makeFaceTestEmployee($this->masterData);

    expect(fn () => $svc->verifyFace($emp, array_fill(0, 128, 0.1)))
        ->toThrow(FaceNotRegisteredException::class, 'belum terdaftar');
});

// ─── Wrong Dimension ───────────────────────────────────────────────────

test('throws BusinessRuleException when vector is not 128D', function () {
    $svc = new FaceRecognitionService;
    $emp = makeFaceTestEmployee($this->masterData);

    // 64-dim
    expect(fn () => $svc->verifyFace($emp, array_fill(0, 64, 0.1)))
        ->toThrow(BusinessRuleException::class, '128');

    // 256-dim
    expect(fn () => $svc->verifyFace($emp, array_fill(0, 256, 0.1)))
        ->toThrow(BusinessRuleException::class, '128');

    // 0-dim
    expect(fn () => $svc->verifyFace($emp, []))
        ->toThrow(BusinessRuleException::class, '128');
});

// ─── Non-numeric Vector ────────────────────────────────────────────────

test('throws BusinessRuleException when vector contains non-numeric values', function () {
    $svc = new FaceRecognitionService;
    $emp = makeFaceTestEmployee($this->masterData);

    // Ensure no descriptor exists
    FaceDescriptor::where('employee_id', $emp->id)->delete();

    $vector = array_fill(0, 128, 0.1);
    $vector[50] = 'not-a-number';

    expect(fn () => $svc->verifyFace($emp, $vector))
        ->toThrow(BusinessRuleException::class);
});

test('throws BusinessRuleException when vector contains null', function () {
    $svc = new FaceRecognitionService;
    $emp = makeFaceTestEmployee($this->masterData);

    // Ensure no descriptor exists
    FaceDescriptor::where('employee_id', $emp->id)->delete();

    $vector = array_fill(0, 128, 0.1);
    $vector[50] = null;

    expect(fn () => $svc->verifyFace($emp, $vector))
        ->toThrow(BusinessRuleException::class);
});

// ─── hasFaceEnrolled ───────────────────────────────────────────────────

test('hasFaceEnrolled returns true when descriptor exists', function () {
    $svc = new FaceRecognitionService;
    $emp = makeFaceTestEmployee($this->masterData);

    FaceDescriptor::create([
        'employee_id' => $emp->id,
        'embedding' => '['.implode(',', array_fill(0, 128, 0.1)).']',
        'is_active' => true,
    ]);

    expect($svc->hasFaceEnrolled($emp))->toBeTrue();
});

test('hasFaceEnrolled returns false when no descriptor', function () {
    $svc = new FaceRecognitionService;
    $emp = makeFaceTestEmployee($this->masterData);

    expect($svc->hasFaceEnrolled($emp))->toBeFalse();
});
