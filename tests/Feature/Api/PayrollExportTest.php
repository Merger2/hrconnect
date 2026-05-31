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

    // Master data minimal untuk Payroll FK chain
    $this->companyId = DB::table('companies')->insertGetId([
        'name' => 'PT Test',
        'code' => 'TST',
        'phone' => '021',
        'email' => 't@t.com',
        'npwp' => '0',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->branchId = DB::table('branches')->insertGetId([
        'company_id' => $this->companyId,
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

    $this->deptId = DB::table('departments')->insertGetId([
        'branch_id' => $this->branchId,
        'name' => 'Eng',
        'code' => 'ENG',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->positionId = DB::table('positions')->insertGetId([
        'department_id' => $this->deptId,
        'name' => 'Staff',
        'code' => 'STF',
        'grade' => 1,
        'basic_salary' => 5_000_000,
        'allowance_jabatan' => 0,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

function makeEmployeeWithRole(string $role, array $masterData): array
{
    $user = User::factory()->create();
    $user->assignRole($role);

    $employee = Employee::create([
        'user_id' => $user->id,
        'employee_number' => 'EMP-'.str()->random(5),
        'full_name' => 'Test User',
        'phone' => '08'.rand(10000000, 99999999),
        'nik' => (string) rand(1000000000000000, 9999999999999999),
        'npwp' => (string) rand(100000, 999999),
        'bank_account_number' => (string) rand(1000000, 9999999),
        'bank_name' => 'BCA',
        'company_id' => $masterData['companyId'],
        'branch_id' => $masterData['branchId'],
        'department_id' => $masterData['deptId'],
        'position_id' => $masterData['positionId'],
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

    $token = $user->createToken('test')->plainTextToken;

    return [$user, $employee, $token];
}

// ─── Payslip endpoint ─────────────────────────────────────────────────

test('GET /payroll/{id}/payslip return 422 untuk DRAFT status', function () {
    [$user, $employee, $token] = makeEmployeeWithRole('finance', [
        'companyId' => $this->companyId,
        'branchId' => $this->branchId,
        'deptId' => $this->deptId,
        'positionId' => $this->positionId,
    ]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-05',
        'basic_salary' => 5_000_000,
        'total_allowance' => 0,
        'gross_salary' => 5_000_000,
        'overtime_pay' => 0,
        'pph21' => 250_000,
        'bpjs_health' => 50_000,
        'bpjs_employment' => 100_000,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 400_000,
        'net_salary' => 4_600_000,
        'status' => PayrollStatus::DRAFT,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson("/api/v1/payroll/{$payroll->id}/payslip")
        ->assertStatus(422);
});

test('Employee tidak bisa download payslip employee lain', function () {
    [$user1, $emp1, $token1] = makeEmployeeWithRole('employee', [
        'companyId' => $this->companyId,
        'branchId' => $this->branchId,
        'deptId' => $this->deptId,
        'positionId' => $this->positionId,
    ]);

    [, $emp2] = makeEmployeeWithRole('employee', [
        'companyId' => $this->companyId,
        'branchId' => $this->branchId,
        'deptId' => $this->deptId,
        'positionId' => $this->positionId,
    ]);

    $payroll = Payroll::create([
        'employee_id' => $emp2->id,  // Bukan owner
        'period' => '2026-05',
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
        'status' => PayrollStatus::PUBLISHED,
    ]);

    $this->withHeader('Authorization', "Bearer {$token1}")
        ->getJson("/api/v1/payroll/{$payroll->id}/payslip")
        ->assertStatus(403);
});

// ─── Excel export endpoints ───────────────────────────────────────────

test('Employee tanpa permission process_payroll dapat 403 di /export/monthly', function () {
    [$user, $employee, $token] = makeEmployeeWithRole('employee', [
        'companyId' => $this->companyId,
        'branchId' => $this->branchId,
        'deptId' => $this->deptId,
        'positionId' => $this->positionId,
    ]);

    $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/payroll/export/monthly', ['period' => '2026-05'])
        ->assertStatus(403);
});

test('Finance bisa export monthly Excel dan return xlsx binary', function () {
    [$user, $employee, $token] = makeEmployeeWithRole('finance', [
        'companyId' => $this->companyId,
        'branchId' => $this->branchId,
        'deptId' => $this->deptId,
        'positionId' => $this->positionId,
    ]);

    Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-05',
        'basic_salary' => 5_000_000,
        'total_allowance' => 1_000_000,
        'gross_salary' => 6_000_000,
        'overtime_pay' => 0,
        'pph21' => 300_000,
        'bpjs_health' => 60_000,
        'bpjs_employment' => 120_000,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 480_000,
        'net_salary' => 5_520_000,
        'status' => PayrollStatus::PUBLISHED,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/payroll/export/monthly', ['period' => '2026-05']);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))
        ->toContain('spreadsheetml');
});

test('Finance bisa export 1721-A1 Excel', function () {
    [$user, $employee, $token] = makeEmployeeWithRole('finance', [
        'companyId' => $this->companyId,
        'branchId' => $this->branchId,
        'deptId' => $this->deptId,
        'positionId' => $this->positionId,
    ]);

    Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-05',
        'basic_salary' => 5_000_000,
        'total_allowance' => 0,
        'gross_salary' => 5_000_000,
        'overtime_pay' => 0,
        'pph21' => 250_000,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 250_000,
        'net_salary' => 4_750_000,
        'status' => PayrollStatus::PUBLISHED,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/payroll/export/1721-a1', ['period' => '2026-05']);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))
        ->toContain('spreadsheetml');
});

test('Finance bisa export BPJS Excel', function () {
    [$user, $employee, $token] = makeEmployeeWithRole('finance', [
        'companyId' => $this->companyId,
        'branchId' => $this->branchId,
        'deptId' => $this->deptId,
        'positionId' => $this->positionId,
    ]);

    Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-05',
        'basic_salary' => 5_000_000,
        'total_allowance' => 0,
        'gross_salary' => 5_000_000,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 50_000,
        'bpjs_employment' => 100_000,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 150_000,
        'net_salary' => 4_850_000,
        'status' => PayrollStatus::PUBLISHED,
    ]);

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/v1/payroll/export/bpjs', ['period' => '2026-05']);

    $response->assertOk();
    expect($response->headers->get('Content-Type'))
        ->toContain('spreadsheetml');
});

test('Export endpoints validasi format periode', function () {
    [$user, $employee, $token] = makeEmployeeWithRole('finance', [
        'companyId' => $this->companyId,
        'branchId' => $this->branchId,
        'deptId' => $this->deptId,
        'positionId' => $this->positionId,
    ]);

    foreach (['monthly', '1721-a1', 'bpjs'] as $type) {
        $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson("/api/v1/payroll/export/{$type}", ['period' => 'invalid'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['period']);
    }
});
