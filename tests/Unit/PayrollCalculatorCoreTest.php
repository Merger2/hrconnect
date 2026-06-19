<?php

use App\Enums\EmploymentType;
use App\Enums\MaritalStatus;
use App\Enums\PayrollStatus;
use App\Enums\TerCategory;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\Overtime;
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

/**
 * Create Overtime with relations for calculateOvertimePay testing.
 * Sets attributes via Eloquent setters to trigger casts.
 */
function payrollTestOvertime(Employee $emp, string $date, string $start, string $end): Overtime
{
    $ot = new Overtime;
    $ot->date = $date;
    $ot->start_time = $start;
    $ot->end_time = $end;
    $ot->setRelation('employee', $emp);

    return $ot;
}

/**
 * Create LeaveBalance record via DB (avoids leave_type factory dependency).
 */
function payrollLeaveBalance(int $employeeId, float $quota, float $used): void
{
    $leaveTypeId = DB::table('leave_types')->insertGetId([
        'name' => 'Annual',
        'code' => 'annual',
        'is_paid' => true,
        'quota' => 12,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('leave_balances')->insert([
        'employee_id' => $employeeId,
        'leave_type_id' => $leaveTypeId,
        'year' => now()->year,
        'quota' => $quota,
        'used' => $used,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
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

    test('regenerate draft preserves existing record and updates values (B-1 fix)', function () {
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
            'employee_number' => 'EMP-TEST-005',
        ]);

        $employee->setRelation('position', Position::find($this->positionId));

        $first = $this->service->generatePayroll($employee, '2026-06');
        $firstId = $first->id;
        $firstNet = (float) $first->net_salary;

        // Second call: should UPDATE the same record
        $second = $this->service->generatePayroll($employee, '2026-06');

        expect($second->id)->toBe($firstId);
        expect($second->status)->toBe(PayrollStatus::DRAFT);
        // net_salary will differ because calculated from scratch
        // but the key assertion is same ID + DRAFT status
        expect((float) $second->basic_salary)->toBe(5_000_000.0);
        expect(Payroll::where('id', $firstId)->count())->toBe(1);
        expect(Payroll::count())->toBe(1);
    });
});

// ─── Payroll Model booted guard ────────────────────────────

function payrollModelGuardSetup(): Employee
{
    $companyId = DB::table('companies')->insertGetId([
        'name' => 'PT Test', 'code' => 'TST', 'phone' => '021',
        'email' => 'test@test.com', 'npwp' => '0', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $branchId = DB::table('branches')->insertGetId([
        'company_id' => $companyId, 'name' => 'HQ', 'address' => 'JKT',
        'is_main' => true, 'is_active' => true,
        'latitude' => -6.2, 'longitude' => 106.8, 'radius' => 100,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $deptId = DB::table('departments')->insertGetId([
        'branch_id' => $branchId, 'name' => 'ENG', 'code' => 'ENG',
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $positionId = DB::table('positions')->insertGetId([
        'department_id' => $deptId, 'name' => 'Staff', 'code' => 'STF',
        'grade' => 1, 'basic_salary' => 5_000_000, 'allowance_jabatan' => 0,
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'department_id' => $deptId,
        'position_id' => $positionId,
        'join_date' => '2020-01-01',
    ]);

    return $employee;
}

function payrollModelGuardPayroll(Employee $emp, PayrollStatus $status): Payroll
{
    return Payroll::create([
        'employee_id' => $emp->id,
        'period' => '2026-06',
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
        'status' => $status,
    ]);
}

test('Payroll model rejects direct update when PUBLISHED', function () {
    $emp = payrollModelGuardSetup();
    $payroll = payrollModelGuardPayroll($emp, PayrollStatus::PUBLISHED);

    expect(fn () => $payroll->update(['basic_salary' => 9_000_000]))
        ->toThrow(BusinessRuleException::class);
});

test('Payroll model rejects direct update when PAID', function () {
    $emp = payrollModelGuardSetup();
    $payroll = payrollModelGuardPayroll($emp, PayrollStatus::PAID);

    expect(fn () => $payroll->update(['basic_salary' => 9_000_000]))
        ->toThrow(BusinessRuleException::class);
});

test('Payroll model allows direct update when DRAFT', function () {
    $emp = payrollModelGuardSetup();
    $payroll = payrollModelGuardPayroll($emp, PayrollStatus::DRAFT);

    $payroll->update(['basic_salary' => 9_000_000]);

    expect((float) $payroll->fresh()->basic_salary)->toBe(9_000_000.0);
});

// ─── calculateOvertimePay ─────────────────────────────────

test('overtime pay zero hours returns 0', function () {
    $emp = payrollTestEmployee();
    $emp = payrollTestPosition($emp, 5_000_000);

    // end == start → 0 hours
    $ot = payrollTestOvertime($emp, '2026-06-15', '2026-06-15 17:00:00', '2026-06-15 17:00:00');

    expect($this->service->calculateOvertimePay($ot))->toBe(0.0);
});

test('overtime pay weekday 1 hour uses 1.5x rate', function () {
    $emp = payrollTestEmployee();
    $emp = payrollTestPosition($emp, 5_000_000);

    $ot = payrollTestOvertime($emp, '2026-06-15', '2026-06-15 17:00:00', '2026-06-15 18:00:00');

    // hourly = (5_000_000 + 0) / 173 ≈ 28,901.73
    // jam-1 = 1 * 28,901.73 * 1.5 = 43,352.60
    expect($this->service->calculateOvertimePay($ot))->toBe(43_352.60);
});

test('overtime pay weekday 3 hours uses 1.5x + 2x for extra', function () {
    $emp = payrollTestEmployee();
    $emp = payrollTestPosition($emp, 5_000_000);

    $ot = payrollTestOvertime($emp, '2026-06-15', '2026-06-15 17:00:00', '2026-06-15 20:00:00');

    // hourly = 5_000_000 / 173 = 28,901.7341
    // jam-1 = 1 * 28,901.73 * 1.5 = 43,352.60
    // jam-2,3 = 2 * 28,901.73 * 2.0 = 115,606.94
    // total = 158,959.54
    expect($this->service->calculateOvertimePay($ot))->toBe(158_959.54);
});

test('overtime pay weekend 3 hours uses tiered 2x/3x/4x', function () {
    $emp = payrollTestEmployee();
    $emp = payrollTestPosition($emp, 5_000_000);

    // Saturday (weekend)
    $ot = payrollTestOvertime($emp, '2026-06-13', '2026-06-13 09:00:00', '2026-06-13 12:00:00');

    // hourly = 5_000_000 / 173 = 28,901.734104...
    // round(3 * hourly * 2.0, 2) = round(173,410.4046, 2) = 173,410.40
    expect($this->service->calculateOvertimePay($ot))->toBe(173_410.40);
});

test('overtime pay weekend 12 hours covers all 3 tiers', function () {
    $emp = payrollTestEmployee();
    $emp = payrollTestPosition($emp, 5_000_000);

    // Saturday
    $ot = payrollTestOvertime($emp, '2026-06-13', '2026-06-13 08:00:00', '2026-06-13 20:00:00');

    // hourly = 5_000_000 / 173 = 28,901.734104...
    // round(8*hourly*2.0 + 2*hourly*3.0 + 2*hourly*4.0, 2)
    // = round(867,052.0231, 2) = 867,052.02
    expect($this->service->calculateOvertimePay($ot))->toBe(867_052.02);
});

test('overtime pay throws when employee has no position', function () {
    $emp = payrollTestEmployee();
    // no position set

    $ot = payrollTestOvertime($emp, '2026-06-15', '2026-06-15 17:00:00', '2026-06-15 20:00:00');

    expect(fn () => $this->service->calculateOvertimePay($ot))
        ->toThrow(BusinessRuleException::class, 'belum memiliki posisi');
});

// ─── calculatePesangon ─────────────────────────────────────

test('pesangon < 1 year returns 0', function () {
    $emp = payrollTestEmployee(['join_date' => now()->subMonths(6)->toDateString()]);
    $emp = payrollTestPosition($emp, 5_000_000);

    expect($this->service->calculatePesangon($emp))->toBe(0.0);
});

test('pesangon 1 year returns 1 month salary', function () {
    $emp = payrollTestEmployee(['join_date' => now()->subYear()->toDateString()]);
    $emp = payrollTestPosition($emp, 5_000_000, 500_000);

    // 1 * 5_500_000 * 1.0(dismissed) = 5_500_000
    expect($this->service->calculatePesangon($emp))->toBe(5_500_000.0);
});

test('pesangon 5 years returns 5 months salary', function () {
    $emp = payrollTestEmployee(['join_date' => now()->subYears(5)->toDateString()]);
    $emp = payrollTestPosition($emp, 7_000_000);

    // 5 * 7_000_000 * 1.0 = 35_000_000
    expect($this->service->calculatePesangon($emp))->toBe(35_000_000.0);
});

test('pesangon 6+ years capped at 6 months salary', function () {
    $emp = payrollTestEmployee(['join_date' => now()->subYears(10)->toDateString()]);
    $emp = payrollTestPosition($emp, 5_000_000);

    // 6 * 5_000_000 * 1.0 = 30_000_000
    expect($this->service->calculatePesangon($emp))->toBe(30_000_000.0);
});

test('pesangon dismissed_severe uses 2x multiplier', function () {
    $emp = payrollTestEmployee([
        'join_date' => now()->subYears(3)->toDateString(),
        'phk_variant' => 'dismissed_severe',
    ]);
    $emp = payrollTestPosition($emp, 5_000_000);

    // 3 * 5_000_000 * 2.0 = 30_000_000
    expect($this->service->calculatePesangon($emp))->toBe(30_000_000.0);
});

test('pesangon mutual uses 0.5x multiplier', function () {
    $emp = payrollTestEmployee([
        'join_date' => now()->subYears(4)->toDateString(),
        'phk_variant' => 'mutual',
    ]);
    $emp = payrollTestPosition($emp, 5_000_000);

    // 4 * 5_000_000 * 0.5 = 10_000_000
    expect($this->service->calculatePesangon($emp))->toBe(10_000_000.0);
});

// ─── calculateUangPenghargaanMasaKerja ─────────────────────

test('UPMK < 3 years returns 0', function () {
    $emp = payrollTestEmployee(['join_date' => now()->subYears(2)->toDateString()]);
    $emp = payrollTestPosition($emp, 5_000_000);

    expect($this->service->calculateUangPenghargaanMasaKerja($emp))->toBe(0.0);
});

test('UPMK 3-6 years returns 2 months salary', function () {
    $emp = payrollTestEmployee(['join_date' => now()->subYears(4)->toDateString()]);
    $emp = payrollTestPosition($emp, 5_000_000);

    // 2 * 5_000_000 * 1.0 = 10_000_000
    expect($this->service->calculateUangPenghargaanMasaKerja($emp))->toBe(10_000_000.0);
});

test('UPMK 6-9 years returns 3 months salary', function () {
    $emp = payrollTestEmployee(['join_date' => now()->subYears(7)->toDateString()]);
    $emp = payrollTestPosition($emp, 5_000_000);

    expect($this->service->calculateUangPenghargaanMasaKerja($emp))->toBe(15_000_000.0);
});

test('UPMK 12-15 years returns 5 months salary', function () {
    $emp = payrollTestEmployee(['join_date' => now()->subYears(13)->toDateString()]);
    $emp = payrollTestPosition($emp, 5_000_000);

    expect($this->service->calculateUangPenghargaanMasaKerja($emp))->toBe(25_000_000.0);
});

test('UPMK 24+ years returns 10 months salary (max)', function () {
    $emp = payrollTestEmployee(['join_date' => now()->subYears(25)->toDateString()]);
    $emp = payrollTestPosition($emp, 5_000_000);

    expect($this->service->calculateUangPenghargaanMasaKerja($emp))->toBe(50_000_000.0);
});

test('UPMK with dismissed_severe variant uses 2x multiplier', function () {
    $emp = payrollTestEmployee([
        'join_date' => now()->subYears(5)->toDateString(),
        'phk_variant' => 'dismissed_severe',
    ]);
    $emp = payrollTestPosition($emp, 5_000_000);

    // 2 months * 5_000_000 * 2.0 = 20_000_000
    expect($this->service->calculateUangPenghargaanMasaKerja($emp))->toBe(20_000_000.0);
});

// ─── calculateUangKompensasi ───────────────────────────────

test('uang kompensasi non-CONTRACT returns 0', function () {
    $emp = payrollTestEmployee(['employment_type' => EmploymentType::PERMANENT->value]);
    $emp = payrollTestPosition($emp, 5_000_000);

    expect($this->service->calculateUangKompensasi($emp))->toBe(0.0);
});

test('uang kompensasi no join_date returns 0', function () {
    $emp = payrollTestEmployee(['join_date' => null, 'employment_type' => EmploymentType::CONTRACT->value]);
    $emp = payrollTestPosition($emp, 5_000_000);

    expect($this->service->calculateUangKompensasi($emp))->toBe(0.0);
});

test('uang kompensasi < 1 month returns 0', function () {
    $emp = payrollTestEmployee([
        'join_date' => now()->subDays(10)->toDateString(),
        'employment_type' => EmploymentType::CONTRACT->value,
    ]);
    $emp = payrollTestPosition($emp, 5_000_000);

    expect($this->service->calculateUangKompensasi($emp))->toBe(0.0);
});

test('uang kompensasi CONTRACT 12 months returns full monthly salary', function () {
    $emp = payrollTestEmployee([
        'join_date' => now()->subYear()->toDateString(),
        'employment_type' => EmploymentType::CONTRACT->value,
        'contract_end_date' => now()->toDateString(),
    ]);
    $emp = payrollTestPosition($emp, 5_000_000, 500_000);

    // (12/12) * 5_500_000 = 5_500_000
    expect($this->service->calculateUangKompensasi($emp))->toBe(5_500_000.0);
});

test('uang kompensasi CONTRACT 6 months returns 6/12 salary', function () {
    $emp = payrollTestEmployee([
        'join_date' => now()->subMonths(6)->toDateString(),
        'employment_type' => EmploymentType::CONTRACT->value,
        'contract_end_date' => now()->toDateString(),
    ]);
    $emp = payrollTestPosition($emp, 6_000_000);

    // (6/12) * 6_000_000 = 3_000_000
    expect($this->service->calculateUangKompensasi($emp))->toBe(3_000_000.0);
});

test('uang kompensasi uses resign_date when available', function () {
    $emp = payrollTestEmployee([
        'join_date' => now()->subMonths(24)->toDateString(),
        'resign_date' => now()->toDateString(),
        'employment_type' => EmploymentType::CONTRACT->value,
    ]);
    $emp = payrollTestPosition($emp, 5_000_000);

    // (24/12) * 5_000_000 = 10_000_000
    expect($this->service->calculateUangKompensasi($emp))->toBe(10_000_000.0);
});

// ─── calculateLeaveCashOut ─────────────────────────────────

function leaveCashOutSetup(): Employee
{
    $companyId = DB::table('companies')->insertGetId([
        'name' => 'PT Test', 'code' => 'TST', 'phone' => '021',
        'email' => 'test@test.com', 'npwp' => '0', 'is_active' => true,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $branchId = DB::table('branches')->insertGetId([
        'company_id' => $companyId, 'name' => 'HQ', 'address' => 'JKT',
        'is_main' => true, 'is_active' => true,
        'latitude' => -6.2, 'longitude' => 106.8, 'radius' => 100,
        'created_at' => now(), 'updated_at' => now(),
    ]);
    $deptId = DB::table('departments')->insertGetId([
        'branch_id' => $branchId, 'name' => 'ENG', 'code' => 'ENG',
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);
    $positionId = DB::table('positions')->insertGetId([
        'department_id' => $deptId, 'name' => 'Staff', 'code' => 'STF',
        'grade' => 1, 'basic_salary' => 5_000_000, 'allowance_jabatan' => 0,
        'is_active' => true, 'created_at' => now(), 'updated_at' => now(),
    ]);

    $user = User::factory()->create();
    $emp = Employee::factory()->create([
        'user_id' => $user->id,
        'company_id' => $companyId,
        'branch_id' => $branchId,
        'department_id' => $deptId,
        'position_id' => $positionId,
        'join_date' => '2020-01-01',
    ]);
    $emp->setRelation('position', Position::find($positionId));

    return $emp;
}

test('leave cash out zero when no leave balance exists', function () {
    $emp = leaveCashOutSetup();

    expect($this->service->calculateLeaveCashOut($emp))->toBe(0.0);
});

test('leave cash out zero when remaining balance is 0', function () {
    $emp = leaveCashOutSetup();

    payrollLeaveBalance($emp->id, 12, 12);

    expect($this->service->calculateLeaveCashOut($emp))->toBe(0.0);
});

test('leave cash out uses remaining balance and daily rate', function () {
    $emp = leaveCashOutSetup();

    payrollLeaveBalance($emp->id, 12, 2);

    $result = $this->service->calculateLeaveCashOut($emp);

    expect($result)->toBeGreaterThan(0.0);
    // remaining = 10, hourly = 5_000_000
    // working days this month (June 2026) = 22
    // daily_rate = 5_000_000 / 22 = 227,272.727...
    // result = 10 * round(5_000_000 / 22, 2) = 2,272,727.27
    expect($result)->toBe(2_272_727.27);
});
