<?php

use App\Enums\RequestStatus;
use App\Models\Employee;
use App\Models\Overtime;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function createOvertime(array $overrides = []): Overtime
{
    return Overtime::create(array_merge([
        'employee_id' => 1,
        'date' => now()->addDay()->toDateString(),
        'start_time' => '17:00:00',
        'end_time' => '20:00:00',
        'description' => 'Test overtime',
        'status' => RequestStatus::PENDING,
    ], $overrides));
}

beforeEach(function () {
    $this->seed(RoleAndPermissionSeeder::class);

    $this->companyId = DB::table('companies')->insertGetId([
        'name' => 'PT Test', 'code' => 'TST', 'phone' => '021',
        'email' => 't@t.com', 'npwp' => '0', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->branchId = DB::table('branches')->insertGetId([
        'company_id' => $this->companyId, 'name' => 'HQ', 'address' => 'JKT',
        'is_main' => true, 'is_active' => true, 'latitude' => -6.2,
        'longitude' => 106.8, 'radius' => 100,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->deptId = DB::table('departments')->insertGetId([
        'branch_id' => $this->branchId, 'name' => 'Eng', 'code' => 'ENG',
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $this->positionId = DB::table('positions')->insertGetId([
        'department_id' => $this->deptId, 'name' => 'Staff', 'code' => 'STF',
        'grade' => 1, 'basic_salary' => 5_000_000, 'allowance_jabatan' => 0,
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $this->employeeUser = User::factory()->create();
    $this->employeeUser->assignRole('employee');
    $this->employee = Employee::create([
        'user_id' => $this->employeeUser->id,
        'employee_number' => 'EMP-001',
        'full_name' => 'Employee One',
        'company_id' => $this->companyId,
        'branch_id' => $this->branchId,
        'department_id' => $this->deptId,
        'position_id' => $this->positionId,
        'phone' => '0811111111',
        'nik' => '3276010101010001',
        'npwp' => '01.001.001.1-001.001',
        'bank_account_number' => '1111111',
        'bank_name' => 'BCA',
        'status' => 'active',
        'gender' => 'L',
        'marital_status' => 'single',
        'blood_type' => 'O+',
        'education_level' => 'bachelor',
        'institution_name' => 'Univ',
        'major' => 'CS',
        'graduation_year' => 2015,
        'birth_date' => '1990-01-01',
        'join_date' => '2025-01-01',
        'salary_type' => 'monthly',
    ]);
    $this->token = $this->employeeUser->createToken('test')->plainTextToken;

    $this->otherUser = User::factory()->create();
    $this->otherUser->assignRole('employee');
    $this->otherEmployee = Employee::create([
        'user_id' => $this->otherUser->id,
        'employee_number' => 'EMP-002',
        'full_name' => 'Employee Two',
        'company_id' => $this->companyId,
        'branch_id' => $this->branchId,
        'department_id' => $this->deptId,
        'position_id' => $this->positionId,
        'phone' => '0822222222',
        'nik' => '3276010101010002',
        'npwp' => '01.001.001.1-001.002',
        'bank_account_number' => '2222222',
        'bank_name' => 'BCA',
        'status' => 'active',
        'gender' => 'L',
        'marital_status' => 'single',
        'blood_type' => 'O+',
        'education_level' => 'bachelor',
        'institution_name' => 'Univ',
        'major' => 'CS',
        'graduation_year' => 2015,
        'birth_date' => '1990-01-01',
        'join_date' => '2025-01-01',
        'salary_type' => 'monthly',
    ]);
    $this->otherToken = $this->otherUser->createToken('test')->plainTextToken;
});

// ─── Auth guards ────────────────────────────────────────────────

test('submit requires authentication', function () {
    $response = $this->postJson('/api/v1/overtime', [
        'date' => now()->addDay()->toDateString(),
        'start_time' => '17:00',
        'end_time' => '20:00',
        'description' => 'Overtime tanpa auth.',
    ]);

    $response->assertStatus(401);
});

test('index requires authentication', function () {
    $response = $this->getJson('/api/v1/overtime');

    $response->assertStatus(401);
});

test('show requires authentication', function () {
    $overtime = createOvertime(['employee_id' => $this->employee->id]);

    $response = $this->getJson("/api/v1/overtime/{$overtime->id}");

    $response->assertStatus(401);
});

test('delete requires authentication', function () {
    $overtime = createOvertime(['employee_id' => $this->employee->id]);

    $response = $this->deleteJson("/api/v1/overtime/{$overtime->id}");

    $response->assertStatus(401);
});

// ─── Owner access ──────────────────────────────────────────────

test('employee can view own overtime', function () {
    $overtime = createOvertime(['employee_id' => $this->employee->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/overtime/{$overtime->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $overtime->id);
});

test('employee cannot view another employee overtime', function () {
    $overtime = createOvertime(['employee_id' => $this->otherEmployee->id]);

    $response = $this->withHeader('Authorization', "Bearer {$this->token}")
        ->getJson("/api/v1/overtime/{$overtime->id}");

    $response->assertStatus(403);
});
