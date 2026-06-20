<?php

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Enums\TerminationType;
use App\Exceptions\BusinessRuleException;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Position;
use App\Models\User;
use App\Services\EmployeeTerminationService;
use App\Services\PayrollCalculatorService;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Fake cast class so that the Employee model's 'vector' cast (pgvector)
 * does not throw InvalidCastException in SQLite tests.
 */
class Vector implements CastsAttributes
{
    public function get($model, string $key, mixed $value, array $attributes): mixed
    {
        return $value !== null ? json_decode($value, true) : null;
    }

    public function set($model, string $key, mixed $value, array $attributes): mixed
    {
        return $value !== null ? json_encode($value) : null;
    }
}

uses(RefreshDatabase::class);

beforeEach(function () {
    $company = Company::factory()->create();
    $branch = Branch::factory()->for($company)->create();
    $department = Department::factory()->for($branch)->create();
    $this->position = Position::factory()->for($department)->create([
        'basic_salary' => 7_000_000,
        'allowance_jabatan' => 500_000,
    ]);

    $this->calculator = $this->createMock(PayrollCalculatorService::class);

    $this->service = new EmployeeTerminationService($this->calculator);
});

// ─── terminate() ────────────────────────────────────────────────────────

test('terminate RESIGN updates status, sets resign_date, soft-deletes user, returns financial_summary', function () {
    $this->calculator->method('calculatePesangon')->willReturn(7_500_000.0);
    $this->calculator->method('calculateUangPenghargaanMasaKerja')->willReturn(14_000_000.0);
    $this->calculator->method('calculateLeaveCashOut')->willReturn(5_000_000.0);

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'position_id' => $this->position->id,
        'status' => EmployeeStatus::ACTIVE,
    ]);

    $result = $this->service->terminate(
        $employee, TerminationType::RESIGN, 'Pindah perusahaan', Carbon::parse('2026-06-01'),
    );

    expect($result->status)->toBe(EmployeeStatus::RESIGNED);
    expect($result->resign_date->toDateString())->toBe('2026-06-01');
    expect($result->termination_type)->toBe(TerminationType::RESIGN);
    expect($result->termination_reason)->toBe('Pindah perusahaan');
    expect(User::withTrashed()->find($result->user_id)->trashed())->toBeTrue();
    expect($result->financial_summary)->toHaveKeys(['pesangon', 'uang_penghargaan_masa_kerja', 'leave_cash_out']);
    expect($result->financial_summary['pesangon'])->toBe(7_500_000.0);
    expect($result->financial_summary['uang_penghargaan_masa_kerja'])->toBe(14_000_000.0);
    expect($result->financial_summary['leave_cash_out'])->toBe(5_000_000.0);
});

test('terminate throws BusinessRuleException for non-active employee', function () {
    $employee = Employee::factory()->create([
        'status' => EmployeeStatus::RESIGNED,
    ]);

    $this->service->terminate($employee, TerminationType::RESIGN, null, Carbon::now());
})->throws(BusinessRuleException::class, 'sudah tidak aktif');

test('terminate DECEASED does not delete user, sets deceased_date', function () {
    $this->calculator->method('calculateLeaveCashOut')->willReturn(5_000_000.0);

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'position_id' => $this->position->id,
        'status' => EmployeeStatus::ACTIVE,
    ]);
    $userId = $employee->user_id;

    $result = $this->service->terminate($employee, TerminationType::DECEASED, null, Carbon::parse('2026-06-15'));

    expect($result->status)->toBe(EmployeeStatus::DECEASED);
    expect($result->deceased_date->toDateString())->toBe('2026-06-15');
    expect($result->resign_date)->toBeNull();

    $freshUser = User::withTrashed()->find($userId);
    expect($freshUser)->not->toBeNull();
    expect($freshUser->trashed())->toBeFalse();
});

test('terminate RESIGN attaches correct financial calculations', function () {
    $this->calculator->method('calculatePesangon')->willReturn(15_000_000.0);
    $this->calculator->method('calculateUangPenghargaanMasaKerja')->willReturn(28_000_000.0);
    $this->calculator->method('calculateLeaveCashOut')->willReturn(3_000_000.0);

    $employee = Employee::factory()->create([
        'position_id' => $this->position->id,
        'status' => EmployeeStatus::ACTIVE,
    ]);

    $result = $this->service->terminate($employee, TerminationType::RESIGN, null, Carbon::now());

    expect($result->financial_summary)->toHaveKeys(['pesangon', 'uang_penghargaan_masa_kerja', 'leave_cash_out']);
    expect($result->financial_summary['pesangon'])->toBe(15_000_000.0);
    expect($result->financial_summary['uang_penghargaan_masa_kerja'])->toBe(28_000_000.0);
    expect($result->financial_summary['leave_cash_out'])->toBe(3_000_000.0);
    expect($result->financial_summary)->not->toHaveKey('uang_kompensasi');
});

// ─── processContractEnd() ───────────────────────────────────────────────

test('processContractEnd terminates expired CONTRACT employees', function () {
    $this->calculator->method('calculateUangKompensasi')->willReturn(12_000_000.0);
    $this->calculator->method('calculateLeaveCashOut')->willReturn(3_000_000.0);

    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'position_id' => $this->position->id,
        'status' => EmployeeStatus::ACTIVE,
        'employment_type' => EmploymentType::CONTRACT,
        'contract_end_date' => Carbon::parse('2026-05-15'),
    ]);

    $count = $this->service->processContractEnd(Carbon::parse('2026-06-01'));

    expect($count)->toBe(1);
    $employee = $employee->fresh();
    expect($employee->status)->toBe(EmployeeStatus::RESIGNED);

    $user->refresh();
    expect($user->trashed())->toBeTrue();
});

test('processContractEnd skips non-expired contracts', function () {
    $user = User::factory()->create();
    Employee::factory()->create([
        'user_id' => $user->id,
        'position_id' => $this->position->id,
        'status' => EmployeeStatus::ACTIVE,
        'employment_type' => EmploymentType::CONTRACT,
        'contract_end_date' => Carbon::parse('2026-07-01'),
    ]);

    $count = $this->service->processContractEnd(Carbon::parse('2026-06-01'));

    expect($count)->toBe(0);
});

test('terminate DISMISSED sets termination_type and reason correctly', function () {
    $this->calculator->method('calculatePesangon')->willReturn(10_000_000.0);
    $this->calculator->method('calculateUangPenghargaanMasaKerja')->willReturn(14_000_000.0);
    $this->calculator->method('calculateLeaveCashOut')->willReturn(2_000_000.0);

    $employee = Employee::factory()->create([
        'position_id' => $this->position->id,
        'status' => EmployeeStatus::ACTIVE,
    ]);

    $result = $this->service->terminate(
        $employee, TerminationType::DISMISSED, 'Efisiensi perusahaan', Carbon::now(),
    );

    expect($result->termination_type)->toBe(TerminationType::DISMISSED);
    expect($result->termination_reason)->toBe('Efisiensi perusahaan');
});

test('terminate CONTRACT_END attaches financial_summary correctly', function () {
    $this->calculator->method('calculateUangKompensasi')->willReturn(12_000_000.0);
    $this->calculator->method('calculateLeaveCashOut')->willReturn(3_000_000.0);

    $employee = Employee::factory()->create([
        'position_id' => $this->position->id,
        'status' => EmployeeStatus::ACTIVE,
    ]);

    $result = $this->service->terminate(
        $employee, TerminationType::CONTRACT_END, null, Carbon::now(),
    );

    expect($result->financial_summary)->toHaveKeys(['uang_kompensasi', 'leave_cash_out']);
    expect($result->financial_summary['uang_kompensasi'])->toBe(12_000_000.0);
    expect($result->financial_summary['leave_cash_out'])->toBe(3_000_000.0);
    expect($result->financial_summary)->not->toHaveKey('pesangon');
    expect($result->financial_summary)->not->toHaveKey('uang_penghargaan_masa_kerja');
});
