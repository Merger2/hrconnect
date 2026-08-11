<?php

namespace App\Mail;

use App\Models\Payroll;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class PayrollPayslipPdfMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Payroll $payroll
    ) {}

    public function build(): self
    {
        // Keputusan Fikih 2026-08-11: PDF payslip dilindungi PIN — email hanya
        // berisi link ke route `payslip.download` (pemilik memasukkan PIN saat
        // download, PDF di-enkripsi dengan PIN plaintext).
        return $this->subject('Slip Gaji '.$this->payroll->period)
            ->markdown('mail.payroll.payslip-pdf', [
                'downloadUrl' => route('payslip.download', $this->payroll),
            ]);
    }
}
