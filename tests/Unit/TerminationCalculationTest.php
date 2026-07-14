<?php

use App\Enums\EmploymentType;
use App\Enums\MaritalStatus;
use App\Enums\TerminationType;
use App\Models\Employee;
use App\Models\Position;
use App\Services\ApprovalService;
use App\Services\PayrollCalculatorService;

beforeEach(function () {
    $this->service = new PayrollCalculatorService(
        $this->createMock(ApprovalService::class),
    );
});

function makeEmployee(array $overrides = []): Employee
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

function withPosition(Employee $emp, float $salary = 7_000_000, float $allowance = 0): Employee
{
    $pos = new Position;
    $pos->basic_salary = $salary;
    $pos->allowance_jabatan = $allowance;

    $emp->setRelation('position', $pos);

    return $emp;
}

// ─── calculatePesangon ─────────────────────────────────────────────────

test('Pesangon < 1 tahun = 0', function () {
    $emp = makeEmployee(['join_date' => now()->subMonths(6)->toDateString()]);
    $emp = withPosition($emp);

    expect($this->service->calculatePesangon($emp))->toBe(0.0);
});

test('Pesangon 1 tahun = 1 bulan salary', function () {
    $emp = makeEmployee(['join_date' => now()->subYear()->toDateString()]);
    $emp = withPosition($emp, 7_000_000, 500_000);

    expect($this->service->calculatePesangon($emp))->toBe(7_500_000.0);
});

test('Pesangon 3 tahun = 3 bulan salary', function () {
    $emp = makeEmployee(['join_date' => now()->subYears(3)->toDateString()]);
    $emp = withPosition($emp, 5_000_000);

    expect($this->service->calculatePesangon($emp))->toBe(15_000_000.0);
});

test('Pesangon 5 tahun = 5 bulan salary', function () {
    $emp = makeEmployee(['join_date' => now()->subYears(5)->toDateString()]);
    $emp = withPosition($emp, 10_000_000);

    expect($this->service->calculatePesangon($emp))->toBe(50_000_000.0);
});

test('Pesangon >= 6 tahun = 6 bulan salary (max)', function () {
    $emp = makeEmployee(['join_date' => now()->subYears(10)->toDateString()]);
    $emp = withPosition($emp, 7_000_000);

    expect($this->service->calculatePesangon($emp))->toBe(42_000_000.0);
});

test('Pesangon dengan phk_variant dismissed_severe = 2x', function () {
    $emp = makeEmployee([
        'join_date' => now()->subYears(3)->toDateString(),
        'phk_variant' => 'dismissed_severe',
    ]);
    $emp = withPosition($emp, 5_000_000);

    expect($this->service->calculatePesangon($emp))->toBe(30_000_000.0);
});

test('Pesangon dengan phk_variant mutual = 0.5x', function () {
    $emp = makeEmployee([
        'join_date' => now()->subYears(4)->toDateString(),
        'phk_variant' => 'mutual',
    ]);
    $emp = withPosition($emp, 10_000_000);

    expect($this->service->calculatePesangon($emp))->toBe(20_000_000.0);
});

// ─── calculateUangPenghargaanMasaKerja ─────────────────────────────────

test('Penghargaan < 3 tahun = 0', function () {
    $emp = makeEmployee(['join_date' => now()->subYears(2)->toDateString()]);
    $emp = withPosition($emp);

    expect($this->service->calculateUangPenghargaanMasaKerja($emp))->toBe(0.0);
});

test('Penghargaan tanpa join date = 0', function () {
    $emp = makeEmployee(['join_date' => null]);
    $emp = withPosition($emp);

    expect($this->service->calculateUangPenghargaanMasaKerja($emp))->toBe(0.0);
});

test('Penghargaan 3-6 tahun = 2 bulan salary', function () {
    $emp = makeEmployee(['join_date' => now()->subYears(4)->toDateString()]);
    $emp = withPosition($emp, 7_000_000);

    expect($this->service->calculateUangPenghargaanMasaKerja($emp))->toBe(14_000_000.0);
});

test('Penghargaan 9-12 tahun = 4 bulan salary', function () {
    $emp = makeEmployee(['join_date' => now()->subYears(10)->toDateString()]);
    $emp = withPosition($emp, 7_000_000);

    expect($this->service->calculateUangPenghargaanMasaKerja($emp))->toBe(28_000_000.0);
});

test('Penghargaan >= 24 tahun = 10 bulan salary (max)', function () {
    $emp = makeEmployee(['join_date' => now()->subYears(25)->toDateString()]);
    $emp = withPosition($emp, 7_000_000);

    expect($this->service->calculateUangPenghargaanMasaKerja($emp))->toBe(70_000_000.0);
});

// ─── calculateUangKompensasi ───────────────────────────────────────────

test('Uang kompensasi untuk PERMANENT = 0', function () {
    $emp = makeEmployee([
        'employment_type' => EmploymentType::PERMANENT->value,
        'join_date' => now()->subYears(2)->toDateString(),
    ]);
    $emp = withPosition($emp);

    expect($this->service->calculateUangKompensasi($emp))->toBe(0.0);
});

test('Uang kompensasi untuk CONTRACT dengan 24 bulan kerja = 2 bulan salary', function () {
    $emp = makeEmployee([
        'employment_type' => EmploymentType::CONTRACT->value,
        'join_date' => now()->subMonths(24)->toDateString(),
        'contract_end_date' => now()->toDateString(),
    ]);
    $emp = withPosition($emp, 6_000_000);

    expect($this->service->calculateUangKompensasi($emp))->toBe(12_000_000.0);
});

test('Uang kompensasi untuk CONTRACT dengan 6 bulan kerja = 0.5 bulan salary', function () {
    $emp = makeEmployee([
        'employment_type' => EmploymentType::CONTRACT->value,
        'join_date' => now()->subMonths(6)->toDateString(),
        'contract_end_date' => now()->toDateString(),
    ]);
    $emp = withPosition($emp, 6_000_000);

    expect($this->service->calculateUangKompensasi($emp))->toBe(3_000_000.0);
});

test('Uang kompensasi untuk CONTRACT < 1 bulan = 0', function () {
    $emp = makeEmployee([
        'employment_type' => EmploymentType::CONTRACT->value,
        'join_date' => now()->subDays(15)->toDateString(),
        'contract_end_date' => now()->toDateString(),
    ]);
    $emp = withPosition($emp, 6_000_000);

    expect($this->service->calculateUangKompensasi($emp))->toBe(0.0);
});

test('Uang kompensasi untuk CONTRACT tanpa join date = 0', function () {
    $emp = makeEmployee([
        'employment_type' => EmploymentType::CONTRACT->value,
        'join_date' => null,
        'contract_end_date' => now()->toDateString(),
    ]);
    $emp = withPosition($emp, 6_000_000);

    expect($this->service->calculateUangKompensasi($emp))->toBe(0.0);
});

test('Uang kompensasi untuk CONTRACT dengan end date sebelum join date = 0', function () {
    $emp = makeEmployee([
        'employment_type' => EmploymentType::CONTRACT->value,
        'join_date' => '2026-06-15',
        'contract_end_date' => '2026-06-01',
    ]);
    $emp = withPosition($emp, 6_000_000);

    expect($this->service->calculateUangKompensasi($emp))->toBe(0.0);
});

// ─── TerminationType helper methods ────────────────────────────────────

test('TerminationType RESIGN requires pesangon, penghargaan, leave cash-out', function () {
    $t = TerminationType::RESIGN;

    expect($t->requiresPesangon())->toBeTrue();
    expect($t->requiresPenghargaan())->toBeTrue();
    expect($t->requiresLeaveCashOut())->toBeTrue();
    expect($t->requiresUangKompensasi())->toBeFalse();
});

test('TerminationType DISMISSED requires pesangon, penghargaan, leave cash-out', function () {
    $t = TerminationType::DISMISSED;

    expect($t->requiresPesangon())->toBeTrue();
    expect($t->requiresPenghargaan())->toBeTrue();
    expect($t->requiresLeaveCashOut())->toBeTrue();
    expect($t->requiresUangKompensasi())->toBeFalse();
});

test('TerminationType CONTRACT_END requires uang kompensasi + leave cash-out', function () {
    $t = TerminationType::CONTRACT_END;

    expect($t->requiresPesangon())->toBeFalse();
    expect($t->requiresPenghargaan())->toBeFalse();
    expect($t->requiresLeaveCashOut())->toBeTrue();
    expect($t->requiresUangKompensasi())->toBeTrue();
});

test('TerminationType DECEASED only requires leave cash-out', function () {
    $t = TerminationType::DECEASED;

    expect($t->requiresPesangon())->toBeFalse();
    expect($t->requiresPenghargaan())->toBeFalse();
    expect($t->requiresLeaveCashOut())->toBeTrue();
    expect($t->requiresUangKompensasi())->toBeFalse();
});
