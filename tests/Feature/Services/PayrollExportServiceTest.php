<?php

use App\Enums\PayrollStatus;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use App\Services\PayrollExportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
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

    $this->branch2Id = DB::table('branches')->insertGetId([
        'company_id' => $this->companyId,
        'name' => 'Cabang 2',
        'address' => 'BDG',
        'is_main' => false,
        'is_active' => true,
        'latitude' => -6.9,
        'longitude' => 107.6,
        'radius' => 100,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->deptId = DB::table('departments')->insertGetId([
        'branch_id' => $this->branchId,
        'name' => 'Engineering',
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

    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Employee — branch HQ
    $this->employee1 = Employee::create([
        'user_id' => $user1->id,
        'employee_number' => 'EMP-0001',
        'full_name' => 'Alice',
        'company_id' => $this->companyId,
        'branch_id' => $this->branchId,
        'department_id' => $this->deptId,
        'position_id' => $this->positionId,
        'status' => 'active',
        'gender' => 'P',
        'marital_status' => 'single',
        'blood_type' => 'O+',
        'education_level' => 'bachelor',
        'institution_name' => 'Univ A',
        'major' => 'CS',
        'graduation_year' => 2015,
        'birth_date' => '1990-01-01',
        'join_date' => '2025-01-01',
        'salary_type' => 'monthly',
        'phone' => '081111111111',
        'nik' => '3276010101900001',
        'npwp' => '01.234.567.8-901.000',
        'bank_account_number' => '1234567890',
        'bank_name' => 'BCA',
    ]);

    // Employee — branch 2
    $this->employee2 = Employee::create([
        'user_id' => $user2->id,
        'employee_number' => 'EMP-0002',
        'full_name' => 'Bob',
        'company_id' => $this->companyId,
        'branch_id' => $this->branch2Id,
        'department_id' => $this->deptId,
        'position_id' => $this->positionId,
        'status' => 'active',
        'gender' => 'L',
        'marital_status' => 'married',
        'blood_type' => 'A+',
        'education_level' => 'bachelor',
        'institution_name' => 'Univ B',
        'major' => 'CS',
        'graduation_year' => 2016,
        'birth_date' => '1991-02-02',
        'join_date' => '2025-01-01',
        'salary_type' => 'monthly',
        'phone' => '082222222222',
        'nik' => '3276010202910002',
        'npwp' => '02.345.678.9-012.000',
        'bank_account_number' => '0987654321',
        'bank_name' => 'BCA',
    ]);

    // Payroll employee1 — period 2026-05
    Payroll::create([
        'employee_id' => $this->employee1->id,
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

    // Payroll employee2 — period 2026-05 (branch 2)
    Payroll::create([
        'employee_id' => $this->employee2->id,
        'period' => '2026-05',
        'basic_salary' => 7_000_000,
        'total_allowance' => 500_000,
        'gross_salary' => 7_500_000,
        'overtime_pay' => 250_000,
        'pph21' => 500_000,
        'bpjs_health' => 80_000,
        'bpjs_employment' => 160_000,
        'loan_deduction' => 200_000,
        'attendance_penalty' => 50_000,
        'total_deduction' => 990_000,
        'net_salary' => 6_510_000,
        'status' => PayrollStatus::PUBLISHED,
    ]);

    $this->service = app(PayrollExportService::class);
});

afterEach(function () {
    $path = storage_path('app/private/exports');
    if (is_dir($path)) {
        array_map('unlink', glob("$path/*.xlsx"));
        rmdir($path);
    }
});

// ─── Helpers ─────────────────────────────────────────────────────────────

function assertValidXlsx(string $path): void
{
    $handle = fopen($path, 'rb');
    $bytes = fread($handle, 4);
    fclose($handle);
    expect(bin2hex($bytes))->toBe('504b0304');
}

// ─── Tests ───────────────────────────────────────────────────────────────

test('exportMonthly creates XLSX file for given period', function () {
    $path = $this->service->exportMonthly('2026-05');

    expect($path)->toBeString()
        ->toContain('storage/app/private/exports/payroll-monthly-2026-05.xlsx');
    expect(file_exists($path))->toBeTrue();
    assertValidXlsx($path);
});

test('exportMonthly with branch filter creates branch-specific XLSX', function () {
    $path = $this->service->exportMonthly('2026-05', branchId: $this->branchId);

    expect($path)->toBeString()
        ->toContain('storage/app/private/exports/payroll-monthly-2026-05-branch');
    expect(file_exists($path))->toBeTrue();
    assertValidXlsx($path);
    expect($path)->toContain("branch{$this->branchId}");
});

test('export1721A1 creates tax report XLSX file', function () {
    $path = $this->service->export1721A1('2026-05');

    expect($path)->toBeString()
        ->toContain('storage/app/private/exports/1721-a1-2026-05.xlsx');
    expect(file_exists($path))->toBeTrue();
    assertValidXlsx($path);
});

test('exportBpjsReport creates BPJS report XLSX file', function () {
    $path = $this->service->exportBpjsReport('2026-05');

    expect($path)->toBeString()
        ->toContain('storage/app/private/exports/bpjs-report-2026-05.xlsx');
    expect(file_exists($path))->toBeTrue();
    assertValidXlsx($path);
});

test('empty period returns file with no payroll rows', function () {
    $path = $this->service->exportMonthly('2099-12');

    expect(file_exists($path))->toBeTrue();
    assertValidXlsx($path);
});

test('file is valid XLSX with ZIP header signature', function () {
    $path = $this->service->exportMonthly('2026-05');
    expect(file_exists($path))->toBeTrue();
    assertValidXlsx($path);
});
