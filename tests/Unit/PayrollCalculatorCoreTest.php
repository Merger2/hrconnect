<?php

use App\Enums\EmploymentType;
use App\Enums\MaritalStatus;
use App\Enums\PayrollStatus;
use App\Enums\TerCategory;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\PayrollCalculatorService;
use Database\Seeders\PayrollConfigSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->service = new PayrollCalculatorService(
        $this->createMock(ApprovalService::class),
    );
    Cache::forget('tax_configs');
    Cache::forget('bpjs_configs');
});

function payrollTestEmployee(array $overrides = []): Employee
{
    $emp = new Employee;
    $ref = new ReflectionClass($emp);
    $prop = $ref->getProperty('attributes');

    $defaults = [
        'id' => 1,
        'full_name' => 'Test Employee',
        'employment_type' => EmploymentType::PERMANENT->value,
        'marital_status' => MaritalStatus::SINGLE->value,
        'join_date' => '2020-01-01',
    ];

    $prop->setValue($emp, [...$defaults, ...$overrides]);

    return $emp;
}

function payrollTestPosition(Employee $emp, float $salary = 7_000_000, float $allowance = 0): Employee
{
    $pos = new Position;
    $pos->basic_salary = $salary;
    $pos->allowance_jabatan = $allowance;

    $emp->setRelation('position', $pos);

    return $emp;
}

// ─── calculateProratedSalary ─────────────────────────────────

test('prorated full month returns full salary when employee joined before month', function () {
    $emp = payrollTestEmployee();
    $emp = payrollTestPosition($emp, 7_000_000);

    expect($this->service->calculateProratedSalary($emp, '2026-06'))->toBe(7_000_000.0);
});

test('prorated join mid-month returns fraction of salary', function () {
    $emp = payrollTestEmployee(['join_date' => '2026-06-15']);
    $emp = payrollTestPosition($emp, 7_000_000);

    // June 2026: 22 working days; from Jun 15 (Mon): 12 working days
    expect($this->service->calculateProratedSalary($emp, '2026-06'))->toBe(3_818_181.82);
});

test('prorated resign mid-month returns fraction of salary', function () {
    $emp = payrollTestEmployee([
        'join_date' => '2020-01-01',
        'resign_date' => '2026-06-15',
    ]);
    $emp = payrollTestPosition($emp, 7_000_000);

    // Jun 1 to Jun 15: 11 working days
    expect($this->service->calculateProratedSalary($emp, '2026-06'))->toBe(3_500_000.0);
});

test('prorated zero actual working days returns 0 when join+resign span only weekend', function () {
    $emp = payrollTestEmployee([
        'join_date' => '2026-06-27', // Saturday
        'resign_date' => '2026-06-28', // Sunday
    ]);
    $emp = payrollTestPosition($emp, 7_000_000);

    expect($this->service->calculateProratedSalary($emp, '2026-06'))->toBe(0.0);
});

test('prorated salary throws when resign date is before join date', function () {
    $emp = payrollTestEmployee([
        'join_date' => '2026-06-15',
        'resign_date' => '2026-06-10',
    ]);
    $emp = payrollTestPosition($emp, 7_000_000);

    expect(fn () => $this->service->calculateProratedSalary($emp, '2026-06'))
        ->toThrow(BusinessRuleException::class, 'Tanggal resign tidak boleh sebelum tanggal join');
});

// ─── calculatePPh21 ──────────────────────────────────────────

test('PPh21 INTERN returns 0 regardless of income', function () {
    $emp = payrollTestEmployee(['employment_type' => EmploymentType::INTERN->value]);

    expect($this->service->calculatePPh21($emp, 10_000_000, TerCategory::A))->toBe(0.0);
});

test('PPh21 valid bracket applies effective_rate from TaxConfig', function () {
    $this->seed(PayrollConfigSeeder::class);
    $emp = payrollTestEmployee();

    // Category A, bracket 0-54M → effective_rate = 0.00
    expect($this->service->calculatePPh21($emp, 5_000_000, TerCategory::A))->toBe(0.0);
});

test('PPh21 no matching bracket returns 0', function () {
    $this->seed(PayrollConfigSeeder::class);
    $emp = payrollTestEmployee();

    // Above max bracket for category A (9,999,999,999)
    expect($this->service->calculatePPh21($emp, 9_999_999_999_999, TerCategory::A))->toBe(0.0);
});

test('PPh21 uses TerCategory to resolve correct bracket set', function () {
    $this->seed(PayrollConfigSeeder::class);
    $emp = payrollTestEmployee();

    // 55M falls in bracket 2 for A (54-56.5M) and bracket 1 for B (0-58.5M)
    // Both have effective_rate 0.00, so results are 0
    expect($this->service->calculatePPh21($emp, 55_000_000, TerCategory::A))->toBe(0.0);
    expect($this->service->calculatePPh21($emp, 55_000_000, TerCategory::B))->toBe(0.0);
});

// ─── calculateBPJS ───────────────────────────────────────────

test('BPJS INTERN returns all zero components', function () {
    $emp = payrollTestEmployee(['employment_type' => EmploymentType::INTERN->value]);

    $result = $this->service->calculateBPJS($emp, 5_000_000);

    foreach (['bpjs_kesehatan', 'bpjs_jht', 'bpjs_jp', 'bpjs_jkk', 'bpjs_jkm'] as $key) {
        expect($result[$key])->toBe(['employer' => 0, 'employee' => 0]);
    }
});

test('BPJS normal employee returns 5 components with correct rates', function () {
    $this->seed(PayrollConfigSeeder::class);
    $emp = payrollTestEmployee();

    $result = $this->service->calculateBPJS($emp, 5_000_000);

    expect($result['bpjs_kesehatan'])->toBe(['employer' => 200_000.0, 'employee' => 50_000.0]);
    expect($result['bpjs_jht'])->toBe(['employer' => 185_000.0, 'employee' => 100_000.0]);
    expect($result['bpjs_jp'])->toBe(['employer' => 100_000.0, 'employee' => 50_000.0]);
    expect($result['bpjs_jkk'])->toBe(['employer' => 12_000.0, 'employee' => 0.0]);
    expect($result['bpjs_jkm'])->toBe(['employer' => 15_000.0, 'employee' => 0.0]);
});

test('BPJS ceiling clamping caps base income per component', function () {
    $this->seed(PayrollConfigSeeder::class);
    $emp = payrollTestEmployee();

    $result = $this->service->calculateBPJS($emp, 15_000_000);

    // kesehatan ceiling=12M → base=12M
    expect($result['bpjs_kesehatan'])->toBe(['employer' => 480_000.0, 'employee' => 120_000.0]);
    // jht no ceiling → base=15M
    expect($result['bpjs_jht'])->toBe(['employer' => 555_000.0, 'employee' => 300_000.0]);
    // jp ceiling=9,559,600 → base=9,559,600
    expect($result['bpjs_jp'])->toBe(['employer' => 191_192.0, 'employee' => 95_596.0]);
    // jkk no ceiling → base=15M
    expect($result['bpjs_jkk'])->toBe(['employer' => 36_000.0, 'employee' => 0.0]);
    // jkm no ceiling → base=15M
    expect($result['bpjs_jkm'])->toBe(['employer' => 45_000.0, 'employee' => 0.0]);
});

// ─── calculateThrProrated ────────────────────────────────────

test('THR < 1 month returns 0', function () {
    expect($this->service->calculateThrProrated(payrollTestEmployee(), 7_000_000, 0))->toBe(0.0);
});

test('THR 6 months returns 6/12 of monthly salary', function () {
    expect($this->service->calculateThrProrated(payrollTestEmployee(), 7_000_000, 6))->toBe(3_500_000.0);
});

test('THR 12 months returns full monthly salary', function () {
    expect($this->service->calculateThrProrated(payrollTestEmployee(), 7_000_000, 12))->toBe(7_000_000.0);
});

// ─── generatePayroll ─────────────────────────────────────────

describe('generatePayroll', function () {
    beforeEach(function () {
        Cache::forget('tax_configs');
        Cache::forget('bpjs_configs');

        $this->companyId = DB::table('companies')->insertGetId([
            'name' => 'PT Test',
            'code' => 'TST',
            'phone' => '021',
            'email' => 'test@test.com',
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
            'allowance_jabatan' => 500_000,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->seed(PayrollConfigSeeder::class);

        DB::table('company_settings')->insert([
            'key' => 'attendance_penalty_per_day',
            'value' => json_encode(50000),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    test('creates DRAFT payroll record with correct field values', function () {
        $user = User::factory()->create();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'company_id' => $this->companyId,
            'branch_id' => $this->branchId,
            'department_id' => $this->deptId,
            'position_id' => $this->positionId,
            'marital_status' => 'single',
            'employment_type' => 'permanent',
            'join_date' => '2020-01-01',
            'employee_number' => 'EMP-TEST-001',
        ]);

        $employee->setRelation('position', Position::find($this->positionId));

        $payroll = $this->service->generatePayroll($employee, '2026-06');

        expect($payroll)->toBeInstanceOf(Payroll::class);
        expect($payroll->employee_id)->toBe($employee->id);
        expect($payroll->period)->toBe('2026-06');
        expect((float) $payroll->basic_salary)->toBe(5_000_000.0);
        expect((float) $payroll->total_allowance)->toBe(500_000.0);
        expect($payroll->status)->toBe(PayrollStatus::DRAFT);
        expect($payroll->exists)->toBeTrue();
    });

    test('throws BusinessRuleException when employee has no position', function () {
        $user = User::factory()->create();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'company_id' => $this->companyId,
            'branch_id' => $this->branchId,
            'department_id' => $this->deptId,
            'position_id' => $this->positionId,
            'marital_status' => 'single',
            'employment_type' => 'permanent',
            'join_date' => '2020-01-01',
            'employee_number' => 'EMP-TEST-002',
        ]);

        $employee->setRelation('position', null);

        expect(fn () => $this->service->generatePayroll($employee, '2026-06'))
            ->toThrow(BusinessRuleException::class, 'belum memiliki jabatan');
    });

    test('throws BusinessRuleException when PUBLISHED payroll already exists for period', function () {
        $user = User::factory()->create();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'company_id' => $this->companyId,
            'branch_id' => $this->branchId,
            'department_id' => $this->deptId,
            'position_id' => $this->positionId,
            'marital_status' => 'single',
            'employment_type' => 'permanent',
            'join_date' => '2020-01-01',
            'employee_number' => 'EMP-TEST-003',
        ]);

        $employee->setRelation('position', Position::find($this->positionId));

        $this->service->generatePayroll($employee, '2026-06');

        Payroll::where('employee_id', $employee->id)
            ->where('period', '2026-06')
            ->update(['status' => PayrollStatus::PUBLISHED]);

        expect(fn () => $this->service->generatePayroll($employee, '2026-06'))
            ->toThrow(BusinessRuleException::class, 'sudah dikunci permanen');
    });

    test('throws BusinessRuleException when payroll generation lock is unavailable', function () {
        $user = User::factory()->create();
        $employee = Employee::factory()->create([
            'user_id' => $user->id,
            'company_id' => $this->companyId,
            'branch_id' => $this->branchId,
            'department_id' => $this->deptId,
            'position_id' => $this->positionId,
            'marital_status' => 'single',
            'employment_type' => 'permanent',
            'join_date' => '2020-01-01',
            'employee_number' => 'EMP-TEST-004',
        ]);

        $employee->setRelation('position', Position::find($this->positionId));
        $lock = Cache::lock("payroll:generate:{$employee->id}:2026-06", 120);

        expect($lock->get())->toBeTrue();

        try {
            expect(fn () => $this->service->generatePayroll($employee, '2026-06'))
                ->toThrow(BusinessRuleException::class, 'sedang diproses');
        } finally {
            $lock->release();
        }
    });
});
