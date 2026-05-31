<?php

namespace App\Console\Commands;

use App\Enums\EmployeeStatus;
use App\Models\Employee;
use App\Services\LeaveService;
use Illuminate\Console\Command;
use Throwable;

/**
 * ResetLeaveQuotaCommand — reset kuota cuti tahunan + carry-forward.
 *
 * Schedule: yearlyOn(1, 1, '00:00') di routes/console.php.
 *
 * Logic per employee aktif:
 * 1. carryForward(prevYear → newYear): bawa max 3 hari sisa cuti tahun lalu
 *    ke tahun baru sebagai 'carry_forward' (B3.5 fix sudah pakai available()
 *    + pertahankan quota employee-specific).
 * 2. initializeBalance(employee, newYear): buat LeaveBalance row baru
 *    untuk semua leave_types aktif yang belum punya entry tahun baru.
 *    Kalau employee join_date di tahun ini → quota di-prorated berdasarkan
 *    bulan kerja.
 *
 * Args:
 * - --from-year: tahun source (default: tahun lalu)
 * - --to-year:   tahun target (default: tahun ini)
 *
 * Idempotent: aman dijalankan ulang. carryForward & initializeBalance pakai
 * updateOrCreate / firstOrCreate.
 */
class ResetLeaveQuotaCommand extends Command
{
    protected $signature = 'leave:reset-quota
                            {--from-year= : Tahun source untuk carry-forward (default: tahun lalu)}
                            {--to-year= : Tahun target (default: tahun ini)}';

    protected $description = 'Reset kuota cuti tahunan + apply carry-forward (max 3 hari) untuk semua karyawan aktif.';

    public function __construct(protected LeaveService $leaveService)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $fromYear = (int) ($this->option('from-year') ?? now()->subYear()->year);
        $toYear = (int) ($this->option('to-year') ?? now()->year);

        $this->info("Reset Leave Quota: carry-forward {$fromYear} → initialize {$toYear}");

        $processed = 0;
        $errors = 0;

        Employee::query()
            ->where('status', EmployeeStatus::ACTIVE->value)
            ->whereNull('resign_date')
            ->chunk(100, function ($employees) use ($fromYear, $toYear, &$processed, &$errors) {
                foreach ($employees as $employee) {
                    try {
                        $this->leaveService->carryForward($employee, $fromYear, $toYear);
                        $this->leaveService->initializeBalance($employee, $toYear);
                        $processed++;
                    } catch (Throwable $e) {
                        $errors++;
                        $this->error("✗ {$employee->employee_number}: {$e->getMessage()}");
                    }
                }
            });

        $this->newLine();
        $this->info("Selesai. Diproses: {$processed} karyawan.");

        if ($errors > 0) {
            $this->warn("Errors: {$errors}");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
