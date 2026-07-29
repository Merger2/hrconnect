<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Employee;
use App\Services\Payroll\PayrollCalculatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

final class GenerateEmployeePayrollJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $timeout = 120;

    public array $backoff = [10, 30, 60];

    public function __construct(
        public Employee $employee,
        public string $period
    ) {
        $this->queue = 'payroll_high';
    }

    public function handle(PayrollCalculatorService $calculator): void
    {
        $calculator->generatePayroll($this->employee, $this->period);

        Log::info('Payroll generated successfully', [
            'employee_id' => $this->employee->id,
            'period' => $this->period,
        ]);
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('Payroll generation failed permanently', [
            'error' => $exception?->getMessage() ?? 'Unknown error',
        ]);
        $this->employee->reimbursements()
            ->whereNull('payroll_id')
            ->where('status', 'paid')
            ->update(['status' => 'approved']);
    }
}
