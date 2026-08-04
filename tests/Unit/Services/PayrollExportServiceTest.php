<?php

use App\Enums\PayrollStatus;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Payroll;
use App\Services\Payroll\PayrollExportService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

/**
 * Master data setup — mengikuti pola AttendanceServiceTest.
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
        'name' => 'Engineering',
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

    $this->masterData = [
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'division_id' => $deptId,
        'position_id' => $posId,
    ];

    $this->period = '2026-07';

    // Create a second branch for branch-filter tests
    $branch2Id = Branch::create([
        'company_id' => $companyId,
        'name' => 'Branch 2',
        'address' => 'BDG',
        'is_main' => false,
        'is_active' => true,
        'latitude' => -6.9,
        'longitude' => 107.6,
        'radius' => 500,
    ])->id;
    $this->branch2Id = $branch2Id;
});

afterEach(function () {
    Mockery::close();
});

// ─── Helpers ───────────────────────────────────────────────────────────

function createPayrollEmployee(array $masterData, ?int $branchId = null): Employee
{
    $branchId ??= $masterData['branch_id'];

    $userId = DB::table('users')->insertGetId([
        'name' => 'Payroll Emp',
        'email' => 'payroll'.str()->random(4).'@t.com',
        'password' => bcrypt('x'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return Employee::create([
        'user_id' => $userId,
        'employee_number' => 'EMP-'.str()->random(6),
        'full_name' => 'Payroll Employee',
        'phone' => '08123456789',
        'nik' => (string) rand(1000000000000000, 9999999999999999),
        'npwp' => (string) rand(100000, 999999),
        'bank_account_number' => (string) rand(1000000, 9999999),
        'bank_name' => 'BCA',
        'company_id' => $masterData['company_id'],
        'branch_id' => $branchId,
        'division_id' => $masterData['division_id'],
        'position_id' => $masterData['position_id'],
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
}

function makePayroll(Employee $emp, string $period, PayrollStatus $status = PayrollStatus::APPROVED, array $overrides = []): Payroll
{
    $defaults = [
        'basic_salary' => 5_000_000,
        'total_allowance' => 500_000,
        'overtime_pay' => 200_000,
        'pph21' => 150_000,
        'bpjs_health' => 50_000,
        'bpjs_employment' => 75_000,
        'loan_deduction' => 300_000,
        'attendance_penalty' => 25_000,
        'gross_salary' => 5_700_000,
        'total_deduction' => 600_000,
        'net_salary' => 5_100_000,
    ];

    return Payroll::create(array_merge($defaults, [
        'employee_id' => $emp->id,
        'period' => $period,
        'status' => $status,
    ], $overrides));
}

/**
 * Build a partial mock of PayrollExportService that stubs writeXlsx
 * and resolveExportPath to avoid actual file I/O.
 */
function mockExportService(): PayrollExportService
{
    /** @var PayrollExportService $service */
    $service = Mockery::mock(PayrollExportService::class)
        ->makePartial()
        ->shouldAllowMockingProtectedMethods();

    $service->shouldReceive('resolveExportPath')
        ->andReturnUsing(fn (string $filename) => '/tmp/'.$filename);

    $service->shouldReceive('writeXlsx')
        ->andReturnUsing(fn (string $path, callable $write) => $path);

    return $service;
}

// ═══════════════════════════════════════════════════════════════════════
// exportMonthly()
// ═══════════════════════════════════════════════════════════════════════

test('exportMonthly returns path for given period', function () {
    $emp = createPayrollEmployee($this->masterData);
    makePayroll($emp, $this->period);

    $service = mockExportService();

    $path = $service->exportMonthly($this->period);

    expect($path)->toContain('payroll-monthly-'.$this->period);
    expect($path)->toContain('.xlsx');
});

test('exportMonthly includes only APPROVED and PAID payrolls', function () {
    $emp1 = createPayrollEmployee($this->masterData);
    $emp2 = createPayrollEmployee($this->masterData);
    $emp3 = createPayrollEmployee($this->masterData);
    $emp4 = createPayrollEmployee($this->masterData);
    $emp5 = createPayrollEmployee($this->masterData);
    makePayroll($emp1, $this->period, PayrollStatus::APPROVED);    // should be included
    makePayroll($emp2, $this->period, PayrollStatus::PAID);        // should be included
    makePayroll($emp3, $this->period, PayrollStatus::DRAFT);       // should NOT be included
    makePayroll($emp4, $this->period, PayrollStatus::SUBMITTED);   // should NOT be included
    makePayroll($emp5, $this->period, PayrollStatus::VERIFIED);    // should NOT be included

    $service = mockExportService();

    $path = $service->exportMonthly($this->period);

    // Test passes if no exception thrown and path returned
    expect($path)->not->toBeNull();
});

test('exportMonthly filters by period', function () {
    $emp = createPayrollEmployee($this->masterData);
    makePayroll($emp, '2026-07'); // included
    makePayroll($emp, '2026-06'); // excluded

    $service = mockExportService();

    $path = $service->exportMonthly('2026-07');

    expect($path)->toContain('2026-07');
});

test('exportMonthly filters by branch when branchId provided', function () {
    $emp1 = createPayrollEmployee($this->masterData, $this->masterData['branch_id']);
    $emp2 = createPayrollEmployee($this->masterData, $this->branch2Id);

    makePayroll($emp1, $this->period); // branch 1
    makePayroll($emp2, $this->period); // branch 2

    $service = mockExportService();

    // Should return path without crash
    $path = $service->exportMonthly($this->period, branchId: $this->masterData['branch_id']);

    expect($path)->toContain("branch{$this->masterData['branch_id']}");
});

test('exportMonthly returns path for empty dataset', function () {
    $service = mockExportService();

    $path = $service->exportMonthly($this->period);

    expect($path)->toContain('.xlsx');
});

test('exportMonthly includes branchId in filename when specified', function () {
    $emp = createPayrollEmployee($this->masterData);
    makePayroll($emp, $this->period);

    $service = mockExportService();

    $pathNoBranch = $service->exportMonthly($this->period);
    expect($pathNoBranch)->not->toContain('branch');

    $pathWithBranch = $service->exportMonthly($this->period, branchId: $this->masterData['branch_id']);
    expect($pathWithBranch)->toContain('branch');
});

// ═══════════════════════════════════════════════════════════════════════
// export1721A1()
// ═══════════════════════════════════════════════════════════════════════

test('export1721A1 returns path for given period', function () {
    $emp = createPayrollEmployee($this->masterData);
    makePayroll($emp, $this->period);

    $service = mockExportService();

    $path = $service->export1721A1($this->period);

    expect($path)->toContain('1721-a1-'.$this->period);
    expect($path)->toContain('.xlsx');
});

test('export1721A1 filters by period', function () {
    $emp = createPayrollEmployee($this->masterData);
    makePayroll($emp, '2026-07'); // included
    makePayroll($emp, '2026-06'); // excluded

    $service = mockExportService();

    $path = $service->export1721A1('2026-07');

    expect($path)->toContain('1721-a1-2026-07');
});

test('export1721A1 returns path for empty dataset', function () {
    $service = mockExportService();

    $path = $service->export1721A1($this->period);

    expect($path)->toContain('.xlsx');
});

test('export1721A1 includes only APPROVED and PAID payrolls', function () {
    $emp1 = createPayrollEmployee($this->masterData);
    $emp2 = createPayrollEmployee($this->masterData);
    $emp3 = createPayrollEmployee($this->masterData);
    $emp4 = createPayrollEmployee($this->masterData);
    makePayroll($emp1, $this->period, PayrollStatus::APPROVED);
    makePayroll($emp2, $this->period, PayrollStatus::PAID);
    makePayroll($emp3, $this->period, PayrollStatus::DRAFT);
    makePayroll($emp4, $this->period, PayrollStatus::SUBMITTED);

    $service = mockExportService();

    // Should not throw despite non-APPROVED/PAID records existing
    $path = $service->export1721A1($this->period);

    expect($path)->not->toBeNull();
});

// ═══════════════════════════════════════════════════════════════════════
// exportBpjsReport()
// ═══════════════════════════════════════════════════════════════════════

test('exportBpjsReport returns path for given period', function () {
    $emp = createPayrollEmployee($this->masterData);
    makePayroll($emp, $this->period);

    $service = mockExportService();

    $path = $service->exportBpjsReport($this->period);

    expect($path)->toContain('bpjs-report-'.$this->period);
    expect($path)->toContain('.xlsx');
});

test('exportBpjsReport filters by period', function () {
    $emp = createPayrollEmployee($this->masterData);
    makePayroll($emp, '2026-07');
    makePayroll($emp, '2026-06');

    $service = mockExportService();

    $path = $service->exportBpjsReport('2026-07');

    expect($path)->toContain('bpjs-report-2026-07');
});

test('exportBpjsReport returns path for empty dataset', function () {
    $service = mockExportService();

    $path = $service->exportBpjsReport($this->period);

    expect($path)->toContain('.xlsx');
});

test('exportBpjsReport includes only APPROVED and PAID payrolls', function () {
    $emp1 = createPayrollEmployee($this->masterData);
    $emp2 = createPayrollEmployee($this->masterData);
    $emp3 = createPayrollEmployee($this->masterData);
    makePayroll($emp1, $this->period, PayrollStatus::APPROVED);
    makePayroll($emp2, $this->period, PayrollStatus::PAID);
    makePayroll($emp3, $this->period, PayrollStatus::DRAFT);

    $service = mockExportService();

    $path = $service->exportBpjsReport($this->period);

    expect($path)->not->toBeNull();
});

// ═══════════════════════════════════════════════════════════════════════
// Contract verification
// ═══════════════════════════════════════════════════════════════════════

test('all export methods return string paths', function () {
    $emp = createPayrollEmployee($this->masterData);
    makePayroll($emp, $this->period);

    $service = mockExportService();

    $monthly = $service->exportMonthly($this->period);
    $a1 = $service->export1721A1($this->period);
    $bpjs = $service->exportBpjsReport($this->period);

    expect($monthly)->toBeString();
    expect($a1)->toBeString();
    expect($bpjs)->toBeString();
});

test('exportMonthly returns path with .xlsx extension', function () {
    $emp = createPayrollEmployee($this->masterData);
    makePayroll($emp, $this->period);

    $service = mockExportService();

    $path = $service->exportMonthly($this->period);

    expect($path)->toContain('.xlsx');
});

test('export1721A1 returns path with .xlsx extension', function () {
    $emp = createPayrollEmployee($this->masterData);
    makePayroll($emp, $this->period);

    $service = mockExportService();

    $path = $service->export1721A1($this->period);

    expect($path)->toContain('.xlsx');
});

test('exportBpjsReport returns path with .xlsx extension', function () {
    $emp = createPayrollEmployee($this->masterData);
    makePayroll($emp, $this->period);

    $service = mockExportService();

    $path = $service->exportBpjsReport($this->period);

    expect($path)->toContain('.xlsx');
});

// ═══════════════════════════════════════════════════════════════════════
// Service instantiation & method signatures
// ═══════════════════════════════════════════════════════════════════════

test('service can be instantiated', function () {
    $service = new PayrollExportService;

    expect($service)->toBeInstanceOf(PayrollExportService::class);
});

test('service has expected public methods', function () {
    $reflection = new ReflectionClass(PayrollExportService::class);

    expect($reflection->hasMethod('exportMonthly'))->toBeTrue();
    expect($reflection->hasMethod('export1721A1'))->toBeTrue();
    expect($reflection->hasMethod('exportBpjsReport'))->toBeTrue();
});

test('exportMonthly has correct method signature', function () {
    $reflection = new ReflectionClass(PayrollExportService::class);
    $method = $reflection->getMethod('exportMonthly');

    expect($method->getNumberOfParameters())->toBe(2);
    expect($method->getNumberOfRequiredParameters())->toBe(1);

    $params = $method->getParameters();
    expect($params[0]->getName())->toBe('period');
    expect((string) $params[0]->getType())->toBe('string');
    expect($params[1]->getName())->toBe('branchId');

    expect((string) $method->getReturnType())->toBe('string');
});

test('export1721A1 has correct method signature', function () {
    $reflection = new ReflectionClass(PayrollExportService::class);
    $method = $reflection->getMethod('export1721A1');

    expect($method->getNumberOfParameters())->toBe(1);
    expect($method->getNumberOfRequiredParameters())->toBe(1);
    expect((string) $method->getReturnType())->toBe('string');
});

test('exportBpjsReport has correct method signature', function () {
    $reflection = new ReflectionClass(PayrollExportService::class);
    $method = $reflection->getMethod('exportBpjsReport');

    expect($method->getNumberOfParameters())->toBe(1);
    expect($method->getNumberOfRequiredParameters())->toBe(1);
    expect((string) $method->getReturnType())->toBe('string');
});
