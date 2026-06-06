<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Payroll;
use App\Services\PayslipPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

#[Queue('payroll_high')]
class GeneratePayslipPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public Payroll $payroll) {}

    public function handle(PayslipPdfService $pdfService): void
    {
        $pdfService->generateAndStore($this->payroll);
    }
}
