<?php

use App\Enums\PayrollStatus;
use App\Livewire\Admin\PayrollManager;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use App\Notifications\PayrollPaid;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

test('admin can submit, verify, approve and pay a payroll record', function () {
    Notification::fake();

    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();
    $employeeRecord = Employee::factory()->create(['user_id' => $employee->id]);

    $payroll = Payroll::create([
        'employee_id' => $employeeRecord->id,
        'period' => now()->format('Y-m'),
        'basic_salary' => 5000000,
        'total_allowance' => 0,
        'gross_salary' => 5000000,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 5000000,
        'payment_method' => 'transfer',
        'status' => PayrollStatus::DRAFT,
    ]);

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->call('submit', (string) $payroll->id)
        ->assertHasNoErrors();

    expect($payroll->refresh()->status)->toBe(PayrollStatus::SUBMITTED);

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->call('verify', (string) $payroll->id)
        ->assertHasNoErrors();

    expect($payroll->refresh()->status)->toBe(PayrollStatus::VERIFIED);

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->call('approve', (string) $payroll->id)
        ->assertHasNoErrors();

    expect($payroll->refresh()->status)->toBe(PayrollStatus::APPROVED);

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->call('markPaid', (string) $payroll->id)
        ->assertHasNoErrors();

    expect($payroll->refresh()->status)->toBe(PayrollStatus::PAID);

    // markPaid wajib mencatat tanggal + metode transfer SEKALIGUS dgn transisi
    // (guard model menolak update terpisah pd payroll PAID — regresi 2026-08-13:
    // 617/617 paid NULL payment_date).
    expect($payroll->payment_date)->not->toBeNull()
        ->and($payroll->payment_method)->toBe('transfer');

    Notification::assertSentTo($employee, PayrollPaid::class, function (PayrollPaid $notification, array $channels) use ($payroll) {
        return $notification->payroll->is($payroll)
            && in_array('database', $channels, true);
    });
});

test('reject returns a payroll back to draft with a rejection reason', function () {
    $admin = User::factory()->admin(true)->create();
    $employee = User::factory()->create();
    $employeeRecord = Employee::factory()->create(['user_id' => $employee->id]);

    $payroll = Payroll::create([
        'employee_id' => $employeeRecord->id,
        'period' => now()->format('Y-m'),
        'basic_salary' => 5000000,
        'total_allowance' => 0,
        'gross_salary' => 5000000,
        'overtime_pay' => 0,
        'pph21' => 0,
        'bpjs_health' => 0,
        'bpjs_employment' => 0,
        'loan_deduction' => 0,
        'attendance_penalty' => 0,
        'total_deduction' => 0,
        'net_salary' => 5000000,
        'payment_method' => 'transfer',
        'status' => PayrollStatus::SUBMITTED,
    ]);

    Livewire::actingAs($admin)
        ->test(PayrollManager::class)
        ->call('confirmReject', (string) $payroll->id)
        ->set('rejectionReason', 'Periode salah')
        ->call('reject')
        ->assertHasNoErrors();

    expect($payroll->refresh()->status)->toBe(PayrollStatus::DRAFT)
        ->and($payroll->rejection_reason)->toBe('Periode salah');
});
