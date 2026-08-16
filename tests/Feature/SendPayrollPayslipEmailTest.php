<?php

declare(strict_types=1);

use App\Enums\PayrollStatus;
use App\Jobs\SendPayrollPayslipEmail;
use App\Mail\PayrollPayslipPdfMail;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

function payslipEmailFixture(): array
{
    $user = User::factory()->create(['email' => 'payslip-'.uniqid().'@hrconnect.test']);
    $employee = Employee::factory()->create(['user_id' => $user->id]);

    $payroll = Payroll::factory()->create([
        'employee_id' => $employee->id,
        'period' => now()->format('Y-m'),
        'status' => PayrollStatus::PAID,
        'payment_method' => 'transfer',
        'payment_date' => now(),
    ]);

    return [$payroll, $user];
}

test('job sends payslip email and records pdf_emailed_at', function () {
    Mail::fake();

    [$payroll, $user] = payslipEmailFixture();

    (new SendPayrollPayslipEmail($payroll->id))->handle();

    Mail::assertSent(PayrollPayslipPdfMail::class, function (PayrollPayslipPdfMail $mail) use ($payroll, $user) {
        return $mail->hasTo($user->email) && $mail->payroll->is($payroll);
    });

    // Regresi 2026-08-13: Payroll::booted() memblokir update payroll PAID — job
    // dulu selalu gagal + kirim email duplikat 3× (retry). Save harus sukses.
    expect($payroll->fresh()->pdf_emailed_at)->not->toBeNull();
});

test('job does not resend email when payroll already emailed', function () {
    Mail::fake();

    [$payroll] = payslipEmailFixture();

    // Set pdf_emailed_at via query builder — guard model menolak update payroll PAID.
    Payroll::query()->whereKey($payroll->id)->update(['pdf_emailed_at' => now()]);

    (new SendPayrollPayslipEmail($payroll->id))->handle();

    Mail::assertNothingSent();
});

test('job skips payroll that is not paid', function () {
    Mail::fake();

    $user = User::factory()->create();
    $employee = Employee::factory()->create(['user_id' => $user->id]);
    $payroll = Payroll::factory()->create([
        'employee_id' => $employee->id,
        'status' => PayrollStatus::APPROVED,
    ]);

    (new SendPayrollPayslipEmail($payroll->id))->handle();

    Mail::assertNothingSent();
    expect($payroll->fresh()->pdf_emailed_at)->toBeNull();
});

test('job does nothing for unknown payroll id', function () {
    Mail::fake();

    (new SendPayrollPayslipEmail(999999))->handle();

    Mail::assertNothingSent();
});
