<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PayrollPayslipPdfMail;
use App\Models\Payroll;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendPayrollPayslipEmail implements ShouldQueue
{
    use Queueable;

    public $tries = 3;

    public $backoff = 60;

    public $timeout = 120;

    public function __construct(public int $payrollId) {}

    public function handle(): void
    {
        $payroll = Payroll::query()
            ->with('employee.user')
            ->whereKey($this->payrollId)
            ->where('status', 'paid')
            ->first();

        if (! $payroll || ! $payroll->employee || ! $payroll->employee->user || ! $payroll->employee->user->email || $payroll->pdf_emailed_at) {
            return;
        }

        // Keputusan Fikih 2026-08-11 ("Minta PIN saat download — paling aman"):
        // PDF payslip TIDAK lagi dilampirkan ke email. Sebelumnya PDF di-enkripsi
        // dengan hash argon2id payslip_password (tidak bisa dibuka siapa pun),
        // dan melampirkan gaji plaintext ke email tidak aman.
        // Email sekarang berisi link ke route web `payslip.download` — pemilik
        // harus memasukkan PIN payslip-nya; PDF di-enkripsi dengan PIN plaintext.
        Mail::to($payroll->employee->user->email)->send(new PayrollPayslipPdfMail($payroll));

        // Guard `Payroll::booted()` memblokir SEMUA update model berstatus PAID
        // (regresi 2026-08-13: `$payroll->save()` selalu BusinessRuleException →
        // job gagal + email duplikat 3× via retry). Update via query builder
        // membypass model event — satu-satunya kolom yang diubah: pdf_emailed_at.
        Payroll::query()->whereKey($payroll->id)->update(['pdf_emailed_at' => now()]);
    }
}
