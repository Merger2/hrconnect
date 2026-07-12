<?php

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Models\Employee;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeProofEmployee(array $overrides = []): Employee
{
    return Employee::create(array_merge([
        'phone' => '081234567890',
        'nik' => '3276010101990001',
        'npwp' => '12.345.678.9-012.345',
        'bank_account_number' => '1234567890',
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
    ], $overrides));
}

function makeManagerEmployee(): User
{
    $user = User::factory()->create();
    $user->assignRole('manager');

    return $user;
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

    $this->targetUser = User::factory()->create();
    $this->targetEmployee = makeProofEmployee([
        'user_id' => $this->targetUser->id,
        'employee_number' => 'EMP-TGT',
        'full_name' => 'Target Employee',
        'company_id' => $this->companyId,
        'branch_id' => $this->branchId,
        'department_id' => $this->deptId,
        'position_id' => $this->positionId,
        'nik' => '3276010101990002',
        'bank_account_number' => '9999999999',
        'status' => EmployeeStatus::ACTIVE,
        'employment_type' => EmploymentType::PERMANENT,
    ]);
});

// ─── 401 Unauthenticated ──────────────────────────────────────────────

test('show without authentication returns 401', function () {
    $response = $this->getJson("/api/v1/employees/{$this->targetEmployee->id}");

    $response->assertStatus(401);
});

test('pii without authentication returns 401', function () {
    $response = $this->getJson("/api/v1/employees/{$this->targetEmployee->id}/pii");

    $response->assertStatus(401);
});

test('terminate without authentication returns 401', function () {
    $response = $this->postJson("/api/v1/employees/{$this->targetEmployee->id}/terminate", [
        'type' => 'resign',
        'reason' => 'Test',
        'date' => '2026-06-30',
    ]);

    $response->assertStatus(401);
});

// ─── Manager role: has view_employees, no manage_employees ────────────

test('manager with view_employees can index', function () {
    $user = makeManagerEmployee();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/v1/employees');

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'status', 'data', 'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
});

test('manager without manage_employees cannot store', function () {
    $user = makeManagerEmployee();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/employees', [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'Secret123!',
        ]);

    $response->assertStatus(403);
});

test('manager without manage_employees cannot update', function () {
    $user = makeManagerEmployee();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->putJson("/api/v1/employees/{$this->targetEmployee->id}", [
            'full_name' => 'Hacked Name',
        ]);

    $response->assertStatus(403);
});

test('manager without manage_employees cannot destroy', function () {
    $user = makeManagerEmployee();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->deleteJson("/api/v1/employees/{$this->targetEmployee->id}");

    $response->assertStatus(403);
});

test('manager without manage_employees cannot view pii', function () {
    $user = makeManagerEmployee();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/employees/{$this->targetEmployee->id}/pii");

    $response->assertStatus(403);
});

test('manager without manage_employees cannot terminate', function () {
    $user = makeManagerEmployee();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson("/api/v1/employees/{$this->targetEmployee->id}/terminate", [
            'type' => 'resign',
            'reason' => 'Test',
            'date' => '2026-06-30',
        ]);

    $response->assertStatus(403);
});

test('manager without manage_employees cannot process contract-end', function () {
    $user = makeManagerEmployee();
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/employees/terminate/contract-end', [
            'date' => '2026-06-30',
        ]);

    $response->assertStatus(403);
});
