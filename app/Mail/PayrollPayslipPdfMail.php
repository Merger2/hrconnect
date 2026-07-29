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
        public Payroll $payroll,
        public string $pdfContent
    ) {}

    public function build(): self
    {
        return $this->subject('Slip Gaji '.$this->payroll->period)
            ->attachData($this->pdfContent, "SlipGaji-{$this->payroll->period}.pdf", [
                'mimetype' => 'application/pdf',
            ])
            ->markdown('mail.payroll.payslip-pdf');
    }
}
