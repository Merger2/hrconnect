<?php

use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

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

    $this->financeUser = User::factory()->create();
    $this->financeUser->assignRole('finance');
    $this->financeEmployee = Employee::create([
        'user_id' => $this->financeUser->id,
        'employee_number' => 'EMP-FIN',
        'full_name' => 'Finance User',
        'phone' => '0811111111',
        'nik' => '3276010101010001',
        'npwp' => '01.001.001.1-001.001',
        'bank_account_number' => '1111111',
        'bank_name' => 'BCA',
        'company_id' => $this->companyId,
        'branch_id' => $this->branchId,
        'department_id' => $this->deptId,
        'position_id' => $this->positionId,
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
    $this->financeToken = $this->financeUser->createToken('test')->plainTextToken;

    $this->employeeUser = User::factory()->create();
    $this->employeeUser->assignRole('employee');
    $this->employee = Employee::create([
        'user_id' => $this->employeeUser->id,
        'employee_number' => 'EMP-REG',
        'full_name' => 'Regular Employee',
        'phone' => '0822222222',
        'nik' => '3276010101010002',
        'npwp' => '01.001.001.1-001.002',
        'bank_account_number' => '2222222',
        'bank_name' => 'BCA',
        'company_id' => $this->companyId,
        'branch_id' => $this->branchId,
        'department_id' => $this->deptId,
        'position_id' => $this->positionId,
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
    $this->employeeToken = $this->employeeUser->createToken('test')->plainTextToken;
});

function createPayroll(array $overrides = []): Payroll
{
    return Payroll::create(array_merge([
        'basic_salary' => 5_000_000,
        'total_allowance' => 0,
        'gross_salary' => 5_000_000,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 5_000_000,
        'status' => PayrollStatus::DRAFT,
    ], $overrides));
}

// ─── Index ────────────────────────────────────────────────────────

test('index returns paginated payroll list for finance', function () {
    createPayroll(['employee_id' => $this->employee->id, 'period' => '2026-04']);
    createPayroll(['employee_id' => $this->employee->id, 'period' => '2026-05']);
    createPayroll(['employee_id' => $this->employee->id, 'period' => '2026-06']);

    $response = $this->withHeader('Authorization', "Bearer {$this->financeToken}")
        ->getJson('/api/v1/payroll');

    $response->assertOk()
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure([
            'data' => [['id', 'employee_id', 'period', 'status', 'gross_salary', 'net_salary']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('meta.total', 3);
});

test('employee can only see own payroll in index', function () {
    createPayroll(['employee_id' => $this->employee->id, 'period' => '2026-04']);
    createPayroll(['employee_id' => $this->financeEmployee->id, 'period' => '2026-04']);

    $response = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
        ->getJson('/api/v1/payroll');

    $response->assertOk()
        ->assertJsonPath('meta.total', 1);
});

test('index filters by year', function () {
    createPayroll(['employee_id' => $this->employee->id, 'period' => '2025-06']);
    createPayroll(['employee_id' => $this->employee->id, 'period' => '2026-06']);

    $response = $this->withHeader('Authorization', "Bearer {$this->financeToken}")
        ->getJson('/api/v1/payroll?year=2025');

    $response->assertOk()
        ->assertJsonPath('meta.total', 1);
});

test('finance can filter index by employee_id', function () {
    createPayroll(['employee_id' => $this->employee->id, 'period' => '2026-04']);
    createPayroll(['employee_id' => $this->financeEmployee->id, 'period' => '2026-05']);

    $response = $this->withHeader('Authorization', "Bearer {$this->financeToken}")
        ->getJson('/api/v1/payroll?employee_id='.$this->financeEmployee->id);

    $response->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.employee_id', $this->financeEmployee->id);
});

test('index validates per_page max', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->financeToken}")
        ->getJson('/api/v1/payroll?per_page=999');

    $response->assertStatus(422);
});

test('index requires authentication', function () {
    $response = $this->getJson('/api/v1/payroll');

    $response->assertStatus(401);
});

// ─── Show ─────────────────────────────────────────────────────────

test('show returns payroll detail for finance', function () {
    $payroll = createPayroll([
        'employee_id' => $this->employee->id,
        'period' => '2026-06',
        'total_allowance' => 1_000_000,
        'gross_salary' => 6_000_000,
        'overtime_pay' => 500_000,
        'pph21' => 300_000,
        'bpjs_health' => 50_000,
        'bpjs_employment' => 100_000,
        'loan_deduction' => 200_000,
        'attendance_penalty' => 50_000,
        'total_deduction' => 700_000,
        'net_salary' => 5_300_000,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->financeToken}")
        ->getJson("/api/v1/payroll/{$payroll->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $payroll->id)
        ->assertJsonPath('data.basic_salary', 5_000_000)
        ->assertJsonPath('data.gross_salary', 6_000_000)
        ->assertJsonPath('data.net_salary', 5_300_000)
        ->assertJsonPath('data.pph21', 300_000)
        ->assertJsonStructure(['data' => [
            'id', 'employee_id', 'period', 'status',
            'basic_salary', 'total_allowance', 'gross_salary',
            'overtime_pay', 'pph21', 'bpjs_health', 'bpjs_employment',
            'loan_deduction', 'attendance_penalty', 'total_deduction', 'net_salary',
        ]]);
});

test('employee can view own payroll detail', function () {
    $payroll = createPayroll([
        'employee_id' => $this->employee->id,
        'period' => '2026-06',
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
        ->getJson("/api/v1/payroll/{$payroll->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $payroll->id);
});

test('employee cannot view other employee payroll', function () {
    $payroll = createPayroll([
        'employee_id' => $this->financeEmployee->id,
        'period' => '2026-06',
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
        ->getJson("/api/v1/payroll/{$payroll->id}");

    $response->assertStatus(403);
});

test('show returns 404 for non-existent payroll', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->financeToken}")
        ->getJson('/api/v1/payroll/99999');

    $response->assertStatus(404);
});

// ─── Generate ─────────────────────────────────────────────────────

test('generate queues job for active employees', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->financeToken}")
        ->postJson('/api/v1/payroll/generate', ['period' => '2026-07']);

    $response->assertStatus(202)
        ->assertJsonPath('status', 'success')
        ->assertJsonStructure(['data' => ['queued_jobs', 'queue', 'period']]);
});

test('generate returns 422 when no active employees', function () {
    $this->employee->update(['status' => 'inactive', 'resign_date' => now()->format('Y-m-d')]);
    $this->financeEmployee->update(['status' => 'inactive', 'resign_date' => now()->format('Y-m-d')]);

    $response = $this->withHeader('Authorization', "Bearer {$this->financeToken}")
        ->postJson('/api/v1/payroll/generate', ['period' => '2026-07']);

    $response->assertStatus(422)
        ->assertJsonPath('status', 'error');
});

test('generate validates period format', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->financeToken}")
        ->postJson('/api/v1/payroll/generate', ['period' => 'invalid']);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['period']);
});

test('generate returns 403 for non-finance user', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
        ->postJson('/api/v1/payroll/generate', ['period' => '2026-07']);

    $response->assertStatus(403);
});

test('generate requires authentication', function () {
    $response = $this->postJson('/api/v1/payroll/generate', ['period' => '2026-07']);

    $response->assertStatus(401);
});

// ─── Payslip ──────────────────────────────────────────────────────

test('payslip returns 422 for draft status', function () {
    $payroll = createPayroll([
        'employee_id' => $this->financeEmployee->id,
        'period' => '2026-06',
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->financeToken}")
        ->getJson("/api/v1/payroll/{$payroll->id}/payslip");

    $response->assertStatus(422);
});

test('payslip returns 403 for cross-employee access', function () {
    $payroll = createPayroll([
        'employee_id' => $this->financeEmployee->id,
        'period' => '2026-06',
        'status' => PayrollStatus::PUBLISHED,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$this->employeeToken}")
        ->getJson("/api/v1/payroll/{$payroll->id}/payslip");

    $response->assertStatus(403);
});

test('payslip requires authentication', function () {
    $payroll = createPayroll([
        'employee_id' => $this->employee->id,
        'period' => '2026-06',
        'status' => PayrollStatus::PUBLISHED,
    ]);

    $response = $this->getJson("/api/v1/payroll/{$payroll->id}/payslip");

    $response->assertStatus(401);
});

// ─── Export ───────────────────────────────────────────────────────

test('export monthly validates period format', function () {
    $response = $this->withHeader('Authorization', "Bearer {$this->financeToken}")
        ->postJson('/api/v1/payroll/export/monthly', ['period' => 'invalid']);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['period']);
});

test('export monthly requires authentication', function () {
    $response = $this->postJson('/api/v1/payroll/export/monthly', ['period' => '2026-06']);

    $response->assertStatus(401);
});
