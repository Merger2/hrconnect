<?php

namespace App\Console\Commands;

use App\Enums\EmployeeStatus;
use App\Jobs\GenerateEmployeePayrollJob;
use App\Models\Employee;
use Carbon\Carbon;
use Illuminate\Console\Command;

/**
 * GeneratePayrollCommand — batch dispatch payroll generation jobs.
 *
 * Schedule: TIDAK auto-scheduled. Trigger manual via Finance UI di Phase 2,
 * atau via cron eksternal kalau finance team mau auto-generate awal bulan.
 *
 * Pattern: dispatch GenerateEmployeePayrollJob per employee aktif. Job di-route
 * ke queue 'payroll_high' (PRD §16) dengan tries=3, timeout=120s, backoff
 * [10, 30, 60].
 *
 * Args:
 * - --period=YYYY-MM : periode target (default: bulan ini)
 * - --employee=ID    : single employee (optional, untuk regenerate satu orang)
 *
 * Output: progress bar + total dispatched count.
 */
class GeneratePayrollCommand extends Command
{
    protected $signature = 'payroll:generate
                            {--period= : Periode YYYY-MM (default: bulan ini)}
                            {--employee= : ID karyawan single (optional)}';

    protected $description = 'Dispatch GenerateEmployeePayrollJob untuk batch payroll generation per periode.';

    public function handle(): int
    {
        $period = $this->option('period') ?: now()->format('Y-m');

        // Validasi format Y-m
        try {
            Carbon::createFromFormat('Y-m', $period);
        } catch (\Exception $e) {
            $this->error("Format periode tidak valid: '{$period}'. Pakai format YYYY-MM (contoh: 2026-05).");

            return self::FAILURE;
        }

        $this->info("Generate Payroll untuk periode: {$period}");

        // Single-employee mode
        if ($employeeId = $this->option('employee')) {
            $employee = Employee::find($employeeId);

            if (! $employee) {
                $this->error("Karyawan ID {$employeeId} tidak ditemukan.");

                return self::FAILURE;
            }

            GenerateEmployeePayrollJob::dispatch($employee, $period);
            $this->info("Job dispatched untuk: {$employee->employee_number} — {$employee->full_name}");

            return self::SUCCESS;
        }

        // Batch mode — semua employee aktif
        $employees = Employee::query()
            ->where('status', EmployeeStatus::ACTIVE->value)
            ->whereNull('resign_date')
            ->get();

        if ($employees->isEmpty()) {
            $this->warn('Tidak ada karyawan aktif untuk diproses.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($employees->count());
        $bar->start();

        $dispatched = 0;
        foreach ($employees as $employee) {
            GenerateEmployeePayrollJob::dispatch($employee, $period);
            $dispatched++;
            $bar->advance();
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("Selesai. Total job dispatched: {$dispatched}");
        $this->line('Queue: payroll_high (tries=3, timeout=120s, backoff=[10,30,60])');

        return self::SUCCESS;
    }
}
