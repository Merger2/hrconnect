<?php

declare(strict_types=1);

use App\Enums\PayrollStatus;
use App\Enums\ReimbursementStatus;
use App\Exceptions\BusinessRuleException;
use App\Jobs\GenerateEmployeePayrollJob;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\Position;
use App\Models\Reimbursement;
use App\Services\Payroll\PayrollCalculatorService;
use Carbon\Carbon;
use Database\Seeders\PayrollConfigSeeder;
use Database\Seeders\TarifTerSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(Carbon::parse('2026-08-04 12:00:00'));

    (new TarifTerSeeder)->run();
    (new PayrollConfigSeeder)->run();
});

// ─── COMMAND: payroll:generate ───

test('payroll:generate command dispatches job for every active employee', function () {
    Queue::fake();

    $employee = Employee::factory()->create([
        'status' => 'active',
        'resign_date' => null,
    ]);

    $this->artisan('payroll:generate', ['--period' => '2026-08'])
        ->assertExitCode(0);

    Queue::assertPushed(GenerateEmployeePayrollJob::class, fn (GenerateEmployeePayrollJob $job) => $job->employee->is($employee) && $job->period === '2026-08');
});

test('payroll:generate command supports single employee mode', function () {
    Queue::fake();

    $employee = Employee::factory()->create();

    $this->artisan('payroll:generate', ['--period' => '2026-08', '--employee' => $employee->id])
        ->assertExitCode(0);

    Queue::assertPushed(GenerateEmployeePayrollJob::class, fn (GenerateEmployeePayrollJob $job) => $job->employee->is($employee) && $job->period === '2026-08');
});

test('payroll:generate command rejects invalid period format', function () {
    $this->artisan('payroll:generate', ['--period' => '08-2026'])
        ->assertExitCode(1);
});

test('payroll:generate command rejects unknown employee id', function () {
    $this->artisan('payroll:generate', ['--period' => '2026-08', '--employee' => 999999])
        ->assertExitCode(1);
});

// ─── JOB: handle ───

test('job generates payroll record for the employee and period', function () {
    $position = Position::factory()->create([
        'basic_salary' => 5_000_000,
        'allowance_jabatan' => 500_000,
    ]);
    $employee = Employee::factory()->create([
        'position_id' => $position->id,
        'join_date' => '2024-01-15',
        'resign_date' => null,
    ]);

    (new GenerateEmployeePayrollJob($employee, '2026-08'))
        ->handle(app(PayrollCalculatorService::class));

    $payroll = Payroll::where('employee_id', $employee->id)
        ->where('period', '2026-08')
        ->first();

    expect($payroll)->not->toBeNull()
        ->and($payroll->status)->toBe(PayrollStatus::DRAFT)
        ->and((float) $payroll->gross_salary)->toBeGreaterThan(0);
});

test('job generation is idempotent for existing draft payroll', function () {
    $position = Position::factory()->create([
        'basic_salary' => 5_000_000,
        'allowance_jabatan' => 500_000,
    ]);
    $employee = Employee::factory()->create([
        'position_id' => $position->id,
        'join_date' => '2024-01-15',
        'resign_date' => null,
    ]);

    $job = new GenerateEmployeePayrollJob($employee, '2026-08');
    $job->handle(app(PayrollCalculatorService::class));
    $job->handle(app(PayrollCalculatorService::class));

    expect(Payroll::where('employee_id', $employee->id)->where('period', '2026-08')->count())->toBe(1);
});

test('job fails loudly when employee has no position', function () {
    $employee = Employee::factory()->create(['position_id' => null]);

    (new GenerateEmployeePayrollJob($employee, '2026-08'))
        ->handle(app(PayrollCalculatorService::class));
})->throws(BusinessRuleException::class);

// ─── JOB: failed ───

test('job failure rolls back unlinked paid reimbursements to approved', function () {
    $employee = Employee::factory()->create();

    $unlinked = Reimbursement::factory()->create([
        'employee_id' => $employee->id,
        'status' => ReimbursementStatus::PAID,
        'payroll_id' => null,
    ]);
    $linked = Reimbursement::factory()->create([
        'employee_id' => $employee->id,
        'status' => ReimbursementStatus::PAID,
        'payroll_id' => Payroll::factory()->create()->id,
    ]);

    (new GenerateEmployeePayrollJob($employee, '2026-08'))
        ->failed(new RuntimeException('Connection lost'));

    expect($unlinked->fresh()->status)->toBe(ReimbursementStatus::APPROVED)
        ->and($linked->fresh()->status)->toBe(ReimbursementStatus::PAID);
});
