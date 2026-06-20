<?php

use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\PayslipPdfService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new PayslipPdfService;

    $userId = DB::table('users')->insertGetId([
        'name' => 'John',
        'email' => 'john@test.com',
        'password' => bcrypt('password'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Company::create([
        'name' => 'PT Test',
        'code' => 'TST',
        'phone' => '021',
        'email' => 't@t.com',
        'npwp' => '01.234.567.8-901.000',
        'is_active' => true,
    ]);

    $branchId = DB::table('branches')->insertGetId([
        'company_id' => 1,
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
        'name' => 'Engineering',
        'code' => 'ENG',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $positionId = DB::table('positions')->insertGetId([
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

    $employee = Employee::create([
        'user_id' => $userId,
        'company_id' => 1,
        'branch_id' => $branchId,
        'department_id' => $deptId,
        'position_id' => $positionId,
        'full_name' => 'John Doe',
        'employee_number' => 'EMP001',
        'employment_type' => 'permanent',
        'status' => 'active',
        'gender' => 'L',
        'salary_type' => 'monthly',
        'phone' => '081111111111',
        'nik' => '3276010101900001',
        'join_date' => '2020-01-01',
        'birth_date' => '1990-01-01',
        'marital_status' => 'single',
        'education_level' => 'bachelor',
        'institution_name' => 'Univ A',
        'graduation_year' => 2015,
    ]);

    $this->payrollId = DB::table('payrolls')->insertGetId([
        'employee_id' => $employee->id,
        'period' => '2026-06',
        'status' => 'published',
        'basic_salary' => 7_000_000,
        'total_allowance' => 1_000_000,
        'overtime_pay' => 500_000,
        'gross_salary' => 8_500_000,
        'pph21' => 300_000,
        'bpjs_health' => 100_000,
        'bpjs_employment' => 150_000,
        'loan_deduction' => 200_000,
        'attendance_penalty' => 50_000,
        'total_deduction' => 800_000,
        'net_salary' => 7_700_000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->payroll = Payroll::find($this->payrollId);
});

test('buildTemplateData returns all required keys', function () {
    $ref = new ReflectionClass($this->service);
    $method = $ref->getMethod('buildTemplateData');
    $data = $method->invoke($this->service, $this->payroll);

    expect($data)->toHaveKeys([
        'payroll', 'period_label', 'company', 'extra_income', 'generated_at',
    ]);
});

test('buildTemplateData period label contains month', function () {
    $ref = new ReflectionClass($this->service);
    $method = $ref->getMethod('buildTemplateData');
    $data = $method->invoke($this->service, $this->payroll);

    expect($data['period_label'])->toContain('Juni');
});

test('buildTemplateData returns company data from database', function () {
    $ref = new ReflectionClass($this->service);
    $method = $ref->getMethod('buildTemplateData');
    $data = $method->invoke($this->service, $this->payroll);

    expect($data['company'])->toHaveKeys(['name', 'address', 'npwp']);
    expect($data['company']['name'])->toBe('PT Test');
});

test('buildTemplateData extra income includes payroll items', function () {
    DB::table('payroll_items')->insert([
        'payroll_id' => $this->payrollId,
        'name' => 'Bonus',
        'type' => 'income',
        'amount' => 500_000,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $ref = new ReflectionClass($this->service);
    $method = $ref->getMethod('buildTemplateData');
    $data = $method->invoke($this->service, $this->payroll);

    expect($data['extra_income'])->toHaveCount(1);
    expect($data['extra_income'][0]['name'])->toBe('Bonus');
    expect($data['extra_income'][0]['amount'])->toBe(500_000);
});

test('buildTemplateData generated_at is a formatted date string', function () {
    $ref = new ReflectionClass($this->service);
    $method = $ref->getMethod('buildTemplateData');
    $data = $method->invoke($this->service, $this->payroll);

    expect($data['generated_at'])->toMatch('/^\d{2} \w+ \d{4} \d{2}:\d{2}$/');
});

test('buildTemplateData payroll contains salary breakdown keys', function () {
    $ref = new ReflectionClass($this->service);
    $method = $ref->getMethod('buildTemplateData');
    $data = $method->invoke($this->service, $this->payroll);

    expect($data['payroll'])->toHaveKeys([
        'basic_salary', 'net_salary', 'gross_salary', 'total_deduction',
        'pph21', 'bpjs_health', 'bpjs_employment',
    ]);
    expect((float) $data['payroll']['net_salary'])->toBe(7_700_000.0);
    expect((float) $data['payroll']['gross_salary'])->toBe(8_500_000.0);
});

test('buildTemplateData employee name from payroll relation', function () {
    $ref = new ReflectionClass($this->service);
    $method = $ref->getMethod('buildTemplateData');
    $data = $method->invoke($this->service, $this->payroll);

    expect($data['payroll']['employee']['full_name'] ?? '')->toBe('John Doe');
});

test('buildTemplateData extra_income empty when no payroll items exist', function () {
    $ref = new ReflectionClass($this->service);
    $method = $ref->getMethod('buildTemplateData');
    $data = $method->invoke($this->service, $this->payroll);

    expect($data['extra_income'])->toBeArray()->toBeEmpty();
});
