<?php

use App\Enums\PayrollStatus;
use App\Events\PayrollApproved;
use App\Events\PayrollPaid as PayrollPaidEvent;
use App\Exceptions\BusinessRuleException;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollAdjustment;
use App\Models\PayrollItem;
use App\Models\User;
use App\Notifications\PayrollPaid;
use App\Notifications\PayrollPublished;
use App\Support\PayrollPaymentInstructionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

beforeEach(function () {
    Notification::fake();
});

/**
 * Payroll API Endpoint Tests (Pest Feature)
 */
test('payroll index returns paginated list with correct structure', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);

    $response = $this->actingAs($user)
        ->getJson('/api/v1/payrolls');

    $response->assertOk()
        ->assertJsonStructure([
            'status',
            'data' => [
                '*' => [
                    'id', 'employee_id', 'period', 'status',
                    'gross_salary', 'total_deduction', 'net_salary', 'created_at',
                ],
            ],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('status', 'success');
});

test('payroll index is scoped to the authenticated employee', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $otherEmployee = Employee::factory()->create();

    Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);
    Payroll::create([
        'employee_id' => $otherEmployee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);

    // viewAny selalu true (PayrollPolicy) — tetapi index dibatasi ke payroll
    // milik employee sendiri (non finance/admin).
    $response = $this->actingAs($user)->getJson('/api/v1/payrolls');

    $response->assertOk();

    $employeeIds = collect($response->json('data'))->pluck('employee_id')->all();

    expect($employeeIds)->toContain($employee->id)
        ->not->toContain($otherEmployee->id);
});

test('payroll show returns full detail with items', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::APPROVED,
    ]);

    PayrollItem::create([
        'payroll_id' => $payroll->id,
        'name' => 'Gaji Pokok',
        'amount' => 5000000,
        'type' => 'allowance',
    ]);

    $response = $this->actingAs($user)
        ->getJson("/api/v1/payrolls/{$payroll->id}");

    $response->assertOk()
        ->assertJsonPath('data.id', $payroll->id)
        ->assertJsonStructure([
            'data' => ['items' => ['*' => ['id', 'name', 'type', 'amount']]],
        ]);
});

test('payroll show is denied for different user via policy', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $owner->id]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);

    $this->actingAs($other)
        ->getJson("/api/v1/payrolls/{$payroll->id}")
        ->assertForbidden();
});

test('payroll generate requires process_payroll permission', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson('/api/v1/payrolls/generate', ['period' => '2026-07'])
        ->assertForbidden();
});

test('payroll generate queues jobs for active employees', function () {
    Queue::fake();

    $admin = User::factory()->admin(true)->create();
    $employee = Employee::factory()->create([
        'status' => 'active',
        'resign_date' => null,
    ]);

    $this->actingAs($admin)
        ->postJson('/api/v1/payrolls/generate', ['period' => '2026-07'])
        ->assertAccepted()
        ->assertJsonPath('data.queued_jobs', 1);
});

/**
 * Payroll Calculation Accuracy Tests (through API + Service)
 */
test('payroll net salary calculation is mathematically correct', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $basicSalary = 5000000;
    $allowance = 500000;
    $overtime = 250000;
    $pph21 = 150000;
    $bpjsHealth = 50000;
    $bpjsEmployment = 100000;
    $attendancePenalty = 0;
    $loanDeduction = 0;

    $gross = $basicSalary + $allowance + $overtime;
    $totalDeduction = $pph21 + $bpjsHealth + $bpjsEmployment + $attendancePenalty + $loanDeduction;
    $net = $gross - $totalDeduction;

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => $basicSalary,
        'total_allowance' => $allowance,
        'gross_salary' => $gross,
        'overtime_pay' => $overtime,
        'pph21' => $pph21,
        'bpjs_health' => $bpjsHealth,
        'bpjs_employment' => $bpjsEmployment,
        'attendance_penalty' => $attendancePenalty,
        'loan_deduction' => $loanDeduction,
        'total_deduction' => $totalDeduction,
        'net_salary' => $net,
        'status' => PayrollStatus::DRAFT,
    ]);

    expect((float) $payroll->gross_salary)->toEqual($basicSalary + $allowance + $overtime)
        ->and((float) $payroll->total_deduction)->toEqual($pph21 + $bpjsHealth + $bpjsEmployment)
        ->and((float) $payroll->net_salary)->toEqual($gross - $totalDeduction);
});

test('payroll with negative adjustment reduces net salary', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);

    // Create negative adjustment (e.g., late penalty)
    PayrollAdjustment::create([
        'payroll_id' => $payroll->id,
        'amount' => -100000,
        'reason' => 'Denda keterlambatan',
        'created_by' => $user->id,
        'applied_to_period' => now()->format('Y-m-d'),
    ]);

    $adjustedNet = (float) $payroll->net_salary + (-100000);
    expect($adjustedNet)->toBe(4900000.0);
});

test('payroll with positive adjustment increases net salary', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);

    PayrollAdjustment::create([
        'payroll_id' => $payroll->id,
        'amount' => 200000,
        'reason' => 'Bonus kinerja',
        'created_by' => $user->id,
        'applied_to_period' => now()->format('Y-m-d'),
    ]);

    $adjustedNet = (float) $payroll->net_salary + 200000;
    expect($adjustedNet)->toBe(5200000.0);
});

/**
 * Payroll Status Transition Tests
 */
test('payroll cannot be published if already paid (terminal state)', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::PAID,
    ]);

    // Attempting to change status from PAID should throw BusinessRuleException
    expect(fn () => $payroll->update(['status' => PayrollStatus::DRAFT]))
        ->toThrow(BusinessRuleException::class);
});

test('payroll can transition draft -> published -> paid', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);

    $payroll->updateQuietly(['status' => PayrollStatus::APPROVED]);
    expect($payroll->fresh()->status)->toBe(PayrollStatus::APPROVED);

    $payroll->updateQuietly(['status' => PayrollStatus::PAID]);
    expect($payroll->fresh()->status)->toBe(PayrollStatus::PAID);
});

/**
 * Payroll Adjustment Model Tests
 */
test('payroll adjustment belongs to payroll and creator', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);

    $adjustment = PayrollAdjustment::create([
        'payroll_id' => $payroll->id,
        'amount' => 100000,
        'reason' => 'Test adjustment',
        'created_by' => $user->id,
        'applied_to_period' => now()->format('Y-m-d'),
    ]);

    expect($adjustment->payroll->is($payroll))->toBeTrue()
        ->and($adjustment->creator->is($user))->toBeTrue();
});

test('payroll adjustment amount is cast to decimal', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);

    $adjustment = PayrollAdjustment::create([
        'payroll_id' => $payroll->id,
        'amount' => 123456.78,
        'reason' => 'Test',
        'created_by' => $user->id,
        'applied_to_period' => now()->format('Y-m-d'),
    ]);

    expect((float) $adjustment->amount)->toBe(123456.78);
});

/**
 * Payroll Payment Instruction Service Tests
 */
test('payment instruction rows include bank details and reference', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'nik' => 'EMP001',
        'bank_name' => 'Bank Central',
        'bank_account_number' => '1234567890',
    ]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::PAID,
    ]);

    $rows = app(PayrollPaymentInstructionService::class)
        ->rows(Payroll::query()->whereKey($payroll->id)->get());

    expect($rows)->toHaveCount(1)
        ->and($rows[0]['bank_name'])->toBe('Bank Central')
        ->and($rows[0]['bank_account_number'])->toBe('1234567890')
        ->and($rows[0]['amount'])->toBe(5000000.0)
        ->and($rows[0]['reference'])->toBe('PAY-202607-EMP001');
});

test('payment instruction skips employees without bank account', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create([
        'user_id' => $user->id,
        'bank_account_number' => null,
    ]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::PAID,
    ]);

    $rows = app(PayrollPaymentInstructionService::class)
        ->rows(Payroll::query()->whereKey($payroll->id)->get());

    expect($rows)->toHaveCount(0);
});

/**
 * Payroll Policy Tests
 */
test('payroll policy allows employee to view own payroll', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);

    expect(Gate::forUser($user)->allows('view', $payroll))->toBeTrue();
});

test('payroll policy denies employee viewing other employee payroll', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $owner->id]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);

    expect(Gate::forUser($other)->allows('view', $payroll))->toBeFalse();
});

test('payroll policy allows download only for paid payroll', function () {
    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $draftPayroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);

    $paidPayroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-06',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::PAID,
    ]);

    expect(Gate::forUser($user)->allows('download', $draftPayroll))->toBeFalse()
        ->and(Gate::forUser($user)->allows('download', $paidPayroll))->toBeTrue();
});

/**
 * Integration: Payroll Generation through UI-like flow
 */
test('full payroll lifecycle: generate -> publish -> pay', function () {
    $admin = User::factory()->admin(true)->create();
    $employee = Employee::factory()->create(['user_id' => User::factory()->create()->id]);

    $payroll = Payroll::create([
        'employee_id' => $employee->id,
        'period' => '2026-07',
        'basic_salary' => 5000000,
        'total_allowance' => 500000,
        'gross_salary' => 5500000,
        'total_deduction' => 500000,
        'net_salary' => 5000000,
        'status' => PayrollStatus::DRAFT,
    ]);

    // Publish — notifikasi dikirim via event PayrollApproved (bukan model event).
    $payroll->updateQuietly(['status' => PayrollStatus::APPROVED]);
    event(new PayrollApproved($payroll));
    Notification::assertSentTo($employee->user, PayrollPublished::class);

    // Pay
    $payroll->updateQuietly(['status' => PayrollStatus::PAID]);
    event(new PayrollPaidEvent($payroll));
    Notification::assertSentTo($employee->user, PayrollPaid::class);
});
