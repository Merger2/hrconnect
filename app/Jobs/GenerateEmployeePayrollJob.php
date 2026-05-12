<?php

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
     * PRD §16: tries: 3.
     */
    public int $tries = 3;

    /**
     * Batas waktu eksekusi job dalam detik.
     * PRD §16: timeout: 120s.
     */
    public int $timeout = 120;

    /**
     * Delay antar retry (dalam detik).
     * PRD §16: backoff [10, 30, 60].
     */
    public array $backoff = [10, 30, 60];

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
