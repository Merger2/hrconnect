<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Payroll;
use App\Services\Payroll\PayslipPdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\Attributes\Queue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

#[Queue('payroll_high')]
final class GeneratePayslipPdfJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public Payroll $payroll
    ) {}

    public function handle(PayslipPdfService $pdfService): void
    {
        $pdfService->generateAndStore($this->payroll);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('PDF generation failed permanently', [
            'error' => $exception?->getMessage() ?? 'Unknown error',
        ]);
        $this->payroll->update(['pdf_path' => null]);
    }
}
