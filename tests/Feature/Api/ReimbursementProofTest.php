<?php

use App\Enums\ReimbursementStatus;
use App\Models\Employee;
use App\Models\Reimbursement;
use App\Models\ReimbursementCategory;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeReimEmployee(array $overrides = []): Employee
{
    return Employee::create(array_merge([
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
    ], $overrides));
}

function createReimCategory(array $overrides = []): ReimbursementCategory
{
    return ReimbursementCategory::create(array_merge([
        'name' => 'Medical',
        'code' => 'MED',
        'is_active' => true,
    ], $overrides));
}

function createReimbursement(array $overrides = []): Reimbursement
{
    return Reimbursement::create(array_merge([
        'employee_id' => null,
        'category_id' => null,
        'amount' => 500_000,
        'title' => 'Test',
        'expense_date' => '2026-06-01',
        'description' => 'Test reimbursement',
        'receipt_file' => 'receipts/test.pdf',
        'status' => ReimbursementStatus::PENDING,
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

    $this->category = createReimCategory(['company_id' => $this->companyId]);

    $this->financeUser = User::factory()->create();
    $this->financeUser->assignRole('finance');
    $this->financeEmployee = makeReimEmployee([
        'user_id' => $this->financeUser->id,
        'employee_number' => 'EMP-FIN',
        'full_name' => 'Finance User',
        'company_id' => $this->companyId,
        'branch_id' => $this->branchId,
        'department_id' => $this->deptId,
        'position_id' => $this->positionId,
        'nik' => '3276010101010001',
        'bank_account_number' => '1111111',
    ]);
    $this->financeToken = $this->financeUser->createToken('test')->plainTextToken;

    $this->employeeUser = User::factory()->create();
    $this->employeeUser->assignRole('employee');
    $this->employee = makeReimEmployee([
        'user_id' => $this->employeeUser->id,
        'employee_number' => 'EMP-REG',
        'full_name' => 'Regular Employee',
        'company_id' => $this->companyId,
        'branch_id' => $this->branchId,
        'department_id' => $this->deptId,
        'position_id' => $this->positionId,
        'nik' => '3276010101010002',
        'bank_account_number' => '2222222',
    ]);
    $this->employeeToken = $this->employeeUser->createToken('test')->plainTextToken;
});

// ─── Authentication ─────────────────────────────────────────────

test('submit without auth returns 401', function () {
    $response = $this->postJson('/api/v1/reimbursement', [
        'category_id' => $this->category->id,
        'amount' => 150_000,
        'description' => 'Test',
        'expense_date' => '2026-06-01',
    ]);

    $response->assertStatus(401);
});

test('index without auth returns 401', function () {
    $response = $this->getJson('/api/v1/reimbursement');

    $response->assertStatus(401);
});

test('show without auth returns 401', function () {
    $reimbursement = createReimbursement([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
    ]);

    $response = $this->getJson("/api/v1/reimbursement/{$reimbursement->id}");

    $response->assertStatus(401);
});

test('delete without auth returns 401', function () {
    $reimbursement = createReimbursement([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
    ]);

    $response = $this->deleteJson("/api/v1/reimbursement/{$reimbursement->id}");

    $response->assertStatus(401);
});

// ─── Authorization / Scoping ────────────────────────────────────

test('employee can view own reimbursement shows 200', function () {
    $reimbursement = createReimbursement([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
        ->getJson("/api/v1/reimbursement/{$reimbursement->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $reimbursement->id);
});

test('employee cannot view another employee reimbursement returns 403', function () {
    $reimbursement = createReimbursement([
        'employee_id' => $this->financeEmployee->id,
        'category_id' => $this->category->id,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
        ->getJson("/api/v1/reimbursement/{$reimbursement->id}");

    $response->assertStatus(403);
});

test('finance can view all reimbursements index shows 200', function () {
    createReimbursement([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
    ]);
    createReimbursement([
        'employee_id' => $this->financeEmployee->id,
        'category_id' => $this->category->id,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->financeToken}")
        ->getJson('/api/v1/reimbursement');

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['data', 'meta'])
        ->assertJsonPath('meta.total', 2);
});

// ─── State-Conflict Delete ──────────────────────────────────────

test('delete own pending reimbursement returns 200', function () {
    $reimbursement = createReimbursement([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'status' => ReimbursementStatus::PENDING,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
        ->deleteJson("/api/v1/reimbursement/{$reimbursement->id}");

    $response->assertOk()
        ->assertJsonPath('status', 'success');

    $this->assertSoftDeleted($reimbursement);
});

test('delete already approved reimbursement returns 403', function () {
    $reimbursement = createReimbursement([
        'employee_id' => $this->employee->id,
        'category_id' => $this->category->id,
        'status' => ReimbursementStatus::APPROVED,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
        ->deleteJson("/api/v1/reimbursement/{$reimbursement->id}");

    $response->assertStatus(403);
});
