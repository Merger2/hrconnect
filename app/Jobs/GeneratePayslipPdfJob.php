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
use Illuminate\Support\Facades\Log;

#[Queue('payroll_high')]
class GeneratePayslipPdfJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60];

    public function __construct(public Payroll $payroll) {}

    public function handle(PayslipPdfService $pdfService): void
    {
        $pdfService->generateAndStore($this->payroll);
    }

    /**
     * B-9: Handle job failure — clear pdf_path and log details.
     */
    public function failed(?\Throwable $exception): void
    {
        Log::error('PDF generation failed permanently', [
            'payroll_id' => $this->payroll->id,
            'period' => $this->payroll->period,
            'error' => $exception?->getMessage(),
        ]);

        $this->payroll->update(['pdf_path' => null]);
    }
}
