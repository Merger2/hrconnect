<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Employee;
use App\Services\PayrollCalculatorService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class GenerateEmployeePayrollJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    /**
     * Jumlah maksimal percobaan jika job gagal.
     */
    public int $tries = 3;

    /**
     * Batas waktu eksekusi job dalam detik.
     */
    public int $timeout = 120;

    /**
     * Delay antar retry (dalam detik).
     */
    public array $backoff = [10, 30, 60];

    /**
     * Queue name untuk job ini (high priority payroll queue).
     * Payroll generation uses dedicated high-priority queue.
     */
    public string $queue = 'payroll_high';

    /**
     * @param  Employee  $employee  Karyawan yang akan digenerate gajinya
     * @param  string  $period  Periode penggajian (format: Y-m)
     */
    public function __construct(
        public Employee $employee,
        public string $period,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(PayrollCalculatorService $calculator): void
    {
        Log::info('Generating payroll', [
            'employee' => $this->employee->full_name,
            'period' => $this->period,
        ]);

        $calculator->generatePayroll($this->employee, $this->period);

        Log::info('Payroll generated successfully', [
            'employee' => $this->employee->full_name,
            'period' => $this->period,
        ]);
    }
}
