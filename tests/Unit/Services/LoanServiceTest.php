<?php

use App\Enums\LoanStatus;
use App\Exceptions\BusinessRuleException;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Employee;
use App\Models\Loan;
use App\Models\User;
use App\Services\HR\LoanService;
use App\Support\ApprovalService;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

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
        'name' => 'Eng',
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

    $this->masterData = compact('companyId', 'branchId', 'deptId', 'posId');

    $this->user = createLoanTestUser($this->masterData);
    $this->employee = $this->user->employee;

    // Mock ApprovalService to isolate LoanService from approval workflow logic
    $this->mockApproval = Mockery::mock(ApprovalService::class);
    $this->service = new LoanService($this->mockApproval);
});

afterEach(function () {
    Mockery::close();
});

// ─── Helpers ───────────────────────────────────────────────────────────

function createLoanTestUser(array $masterData)
{
    $userId = DB::table('users')->insertGetId([
        'name' => 'Loan Tester',
        'email' => 'loan.tester@t.com',
        'password' => bcrypt('x'),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Employee::create([
        'user_id' => $userId,
        'employee_number' => 'EMP-'.str()->random(6),
        'full_name' => 'Loan Tester',
        'phone' => '08123456789',
        'nik' => (string) rand(1000000000000000, 9999999999999999),
        'npwp' => (string) rand(100000, 999999),
        'bank_account_number' => (string) rand(1000000, 9999999),
        'bank_name' => 'BCA',
        'company_id' => $masterData['companyId'],
        'branch_id' => $masterData['branchId'],
        'division_id' => $masterData['deptId'],
        'position_id' => $masterData['posId'],
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

    return User::find($userId);
}

// ═══════════════════════════════════════════════════════════════════════
// createLoan()
// ═══════════════════════════════════════════════════════════════════════

test('createLoan creates loan with correct data and PENDING status', function () {
    $this->mockApproval
        ->shouldReceive('createApprovalWorkflow')
        ->once()
        ->with(Mockery::type(Loan::class));

    $loan = $this->service->createLoan($this->employee, [
        'amount' => 10_000_000,
        'interest_rate' => 0,
        'tenor_months' => 12,
    ]);

    expect($loan)->toBeInstanceOf(Loan::class);
    expect($loan->employee_id)->toBe($this->employee->id);
    expect((float) $loan->amount)->toEqual(10_000_000.0);
    expect((float) $loan->interest_rate)->toEqual(0.0);
    expect($loan->tenor_months)->toBe(12);
    expect((float) $loan->monthly_installment)->toEqual(833_333.33); // 10jt / 12
    expect($loan->status->value)->toBe('pending');
    expect($loan->is_settled)->toBeFalse();
});

test('createLoan creates loan with interest rate', function () {
    $this->mockApproval
        ->shouldReceive('createApprovalWorkflow')
        ->once()
        ->with(Mockery::type(Loan::class));

    $loan = $this->service->createLoan($this->employee, [
        'amount' => 10_000_000,
        'interest_rate' => 12, // 12% per tahun
        'tenor_months' => 12,
    ]);

    expect($loan)->toBeInstanceOf(Loan::class);
    expect((float) $loan->amount)->toEqual(10_000_000.0);
    expect((float) $loan->interest_rate)->toEqual(12.0);

    // Monthly installment with 12% annual interest for 12 months
    // Using amortization formula: P * (r(1+r)^n) / ((1+r)^n - 1)
    // where r = 0.12/12 = 0.01, n = 12
    // = 10_000_000 * (0.01 * (1.01)^12) / ((1.01)^12 - 1)
    // Expected: 888,487.88 (rounded)
    expect((float) $loan->monthly_installment)->toBeGreaterThan(888_000.0);
    expect((float) $loan->monthly_installment)->toBeLessThan(889_000.0);
});

test('createLoan calls createApprovalWorkflow', function () {
    $this->mockApproval
        ->shouldReceive('createApprovalWorkflow')
        ->once()
        ->with(Mockery::type(Loan::class));

    $loan = $this->service->createLoan($this->employee, [
        'amount' => 5_000_000,
        'interest_rate' => 0,
        'tenor_months' => 6,
    ]);

    // Approval workflow was called — verified by Mockery expectation above
    expect($loan->id)->not->toBeNull();
});

test('createLoan returns loan with fresh employee relation loaded', function () {
    $this->mockApproval
        ->shouldReceive('createApprovalWorkflow')
        ->once()
        ->with(Mockery::type(Loan::class));

    $loan = $this->service->createLoan($this->employee, [
        'amount' => 3_000_000,
        'interest_rate' => 0,
        'tenor_months' => 3,
    ]);

    expect($loan->relationLoaded('employee'))->toBeTrue();
    expect($loan->employee->id)->toBe($this->employee->id);
});

// ═══════════════════════════════════════════════════════════════════════
// updateLoan()
// ═══════════════════════════════════════════════════════════════════════

test('updateLoan updates pending loan', function () {
    $loan = Loan::create([
        'employee_id' => $this->employee->id,
        'amount' => 10_000_000,
        'interest_rate' => 0,
        'tenor_months' => 12,
        'monthly_installment' => 833_333.33,
        'status' => LoanStatus::PENDING,
    ]);

    $updated = $this->service->updateLoan($loan, [
        'amount' => 8_000_000,
    ]);

    expect((float) $updated->amount)->toEqual(8_000_000.0);
    expect((float) $updated->monthly_installment)->toEqual(666_666.67); // 8jt / 12
    expect($updated->status->value)->toBe('pending');
});

test('updateLoan throws when loan is not PENDING', function () {
    $loan = Loan::create([
        'employee_id' => $this->employee->id,
        'amount' => 10_000_000,
        'interest_rate' => 0,
        'tenor_months' => 12,
        'monthly_installment' => 833_333.33,
        'status' => LoanStatus::APPROVED,
    ]);

    expect(fn () => $this->service->updateLoan($loan, ['amount' => 8_000_000]))
        ->toThrow(BusinessRuleException::class, 'Hanya pinjaman dengan status PENDING');
});

test('updateLoan recalculates installment when tenor changes', function () {
    $loan = Loan::create([
        'employee_id' => $this->employee->id,
        'amount' => 6_000_000,
        'interest_rate' => 0,
        'tenor_months' => 12,
        'monthly_installment' => 500_000,
        'status' => LoanStatus::PENDING,
    ]);

    $updated = $this->service->updateLoan($loan, [
        'tenor_months' => 6,
    ]);

    expect($updated->tenor_months)->toBe(6);
    expect((float) $updated->monthly_installment)->toEqual(1_000_000.0); // 6jt / 6
});

test('updateLoan recalculates installment when interest rate changes', function () {
    $loan = Loan::create([
        'employee_id' => $this->employee->id,
        'amount' => 10_000_000,
        'interest_rate' => 0,
        'tenor_months' => 12,
        'monthly_installment' => 833_333.33,
        'status' => LoanStatus::PENDING,
    ]);

    $updated = $this->service->updateLoan($loan, [
        'interest_rate' => 12, // 12% per tahun
    ]);

    expect((float) $updated->interest_rate)->toEqual(12.0);
    expect((float) $updated->monthly_installment)->toBeGreaterThan(888_000.0);
    expect((float) $updated->monthly_installment)->toBeLessThan(889_000.0);
});

test('updateLoan does not recalculate when amount/interest/tenor unchanged', function () {
    $loan = Loan::create([
        'employee_id' => $this->employee->id,
        'amount' => 5_000_000,
        'interest_rate' => 0,
        'tenor_months' => 10,
        'monthly_installment' => 500_000,
        'status' => LoanStatus::PENDING,
    ]);

    $updated = $this->service->updateLoan($loan, [
        'monthly_installment' => 600_000, // ignored — not a recalculation trigger
    ]);

    // monthly_installment IS in fillable, so direct field updates apply
    expect((float) $updated->monthly_installment)->toEqual(600_000.0);
});

// ═══════════════════════════════════════════════════════════════════════
// cancelLoan()
// ═══════════════════════════════════════════════════════════════════════

test('cancelLoan cancels pending loan', function () {
    $loan = Loan::create([
        'employee_id' => $this->employee->id,
        'amount' => 5_000_000,
        'interest_rate' => 0,
        'tenor_months' => 12,
        'monthly_installment' => 416_666.67,
        'status' => LoanStatus::PENDING,
    ]);

    $this->service->cancelLoan($loan);

    $loan->refresh();
    expect($loan->status->value)->toBe('cancelled');
});

test('cancelLoan cancels approved loan', function () {
    $loan = Loan::create([
        'employee_id' => $this->employee->id,
        'amount' => 5_000_000,
        'interest_rate' => 0,
        'tenor_months' => 12,
        'monthly_installment' => 416_666.67,
        'status' => LoanStatus::APPROVED,
    ]);

    $this->service->cancelLoan($loan);

    $loan->refresh();
    expect($loan->status->value)->toBe('cancelled');
});

test('cancelLoan throws for ACTIVE loan', function () {
    $loan = Loan::create([
        'employee_id' => $this->employee->id,
        'amount' => 5_000_000,
        'interest_rate' => 0,
        'tenor_months' => 12,
        'monthly_installment' => 416_666.67,
        'status' => LoanStatus::ACTIVE,
    ]);

    expect(fn () => $this->service->cancelLoan($loan))
        ->toThrow(BusinessRuleException::class, 'Pinjaman tidak dapat dibatalkan');
});

test('cancelLoan throws for REJECTED loan', function () {
    $loan = Loan::create([
        'employee_id' => $this->employee->id,
        'amount' => 5_000_000,
        'interest_rate' => 0,
        'tenor_months' => 12,
        'monthly_installment' => 416_666.67,
        'status' => LoanStatus::REJECTED,
    ]);

    expect(fn () => $this->service->cancelLoan($loan))
        ->toThrow(BusinessRuleException::class, 'Pinjaman tidak dapat dibatalkan');
});

test('cancelLoan throws for PAID_OFF loan', function () {
    $loan = Loan::create([
        'employee_id' => $this->employee->id,
        'amount' => 5_000_000,
        'interest_rate' => 0,
        'tenor_months' => 12,
        'monthly_installment' => 416_666.67,
        'status' => LoanStatus::PAID_OFF,
    ]);

    expect(fn () => $this->service->cancelLoan($loan))
        ->toThrow(BusinessRuleException::class, 'Pinjaman tidak dapat dibatalkan');
});

// ═══════════════════════════════════════════════════════════════════════
// calculateMonthlyInstallment() — diakses via createLoan/updateLoan
// ═══════════════════════════════════════════════════════════════════════

test('calculateMonthlyInstallment throws for zero tenor', function () {
    $this->mockApproval
        ->shouldReceive('createApprovalWorkflow')
        ->never();

    expect(fn () => $this->service->createLoan($this->employee, [
        'amount' => 5_000_000,
        'interest_rate' => 0,
        'tenor_months' => 0,
    ]))->toThrow(BusinessRuleException::class, 'Tenor harus lebih dari 0 bulan');
});

test('calculateMonthlyInstallment handles large amount with zero interest', function () {
    $this->mockApproval
        ->shouldReceive('createApprovalWorkflow')
        ->once()
        ->with(Mockery::type(Loan::class));

    $loan = $this->service->createLoan($this->employee, [
        'amount' => 100_000_000,
        'interest_rate' => 0,
        'tenor_months' => 24,
    ]);

    expect((float) $loan->monthly_installment)->toEqual(4_166_666.67); // 100jt / 24
});

test('calculateMonthlyInstallment handles 1-month tenor', function () {
    $this->mockApproval
        ->shouldReceive('createApprovalWorkflow')
        ->once()
        ->with(Mockery::type(Loan::class));

    $loan = $this->service->createLoan($this->employee, [
        'amount' => 5_000_000,
        'interest_rate' => 0,
        'tenor_months' => 1,
    ]);

    expect((float) $loan->monthly_installment)->toEqual(5_000_000.0);
});

test('calculateMonthlyInstallment handles very small interest rate', function () {
    $this->mockApproval
        ->shouldReceive('createApprovalWorkflow')
        ->once()
        ->with(Mockery::type(Loan::class));

    $loan = $this->service->createLoan($this->employee, [
        'amount' => 12_000_000,
        'interest_rate' => 0.5, // 0.5% per tahun — sangat kecil
        'tenor_months' => 12,
    ]);

    // Dengan bunga 0.5%, installment harus > 1_000_000 (pokok) tapi tidak terlalu besar
    expect((float) $loan->monthly_installment)->toBeGreaterThan(1_000_000);
    expect((float) $loan->monthly_installment)->toBeLessThan(1_005_000);
});

// ═══════════════════════════════════════════════════════════════════════
// Contract verification
// ═══════════════════════════════════════════════════════════════════════

test('service can be instantiated', function () {
    $service = new LoanService($this->mockApproval);
    expect($service)->toBeInstanceOf(LoanService::class);
});

test('service has expected public methods', function () {
    $reflection = new ReflectionClass(LoanService::class);

    expect($reflection->hasMethod('createLoan'))->toBeTrue();
    expect($reflection->hasMethod('updateLoan'))->toBeTrue();
    expect($reflection->hasMethod('cancelLoan'))->toBeTrue();
});

test('createLoan returns Loan instance', function () {
    $this->mockApproval
        ->shouldReceive('createApprovalWorkflow')
        ->once()
        ->with(Mockery::type(Loan::class));

    $loan = $this->service->createLoan($this->employee, [
        'amount' => 1_000_000,
        'interest_rate' => 0,
        'tenor_months' => 1,
    ]);

    expect($loan)->toBeInstanceOf(Loan::class);
});
