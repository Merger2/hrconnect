<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\PayrollPayslipPdfMail;
use App\Models\Payroll;
use App\Services\Payroll\PayslipPdfService;
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

    public function handle(PayslipPdfService $pdfService): void
    {
        $payroll = Payroll::query()
            ->with('employee.user')
            ->whereKey($this->payrollId)
            ->where('status', 'paid')
            ->first();

        if (! $payroll || ! $payroll->employee || ! $payroll->employee->user || ! $payroll->employee->user->email || $payroll->pdf_emailed_at) {
            return;
        }

        $password = $payroll->employee->payslip_password;

        $pdfContent = $pdfService->generate($payroll, $password);

        Mail::to($payroll->employee->user->email)->send(new PayrollPayslipPdfMail(
            $payroll,
            $pdfContent,
        ));

        $payroll->forceFill(['pdf_emailed_at' => now()])->save();
    }
}
