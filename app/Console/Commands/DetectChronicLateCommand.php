<?php

namespace App\Console\Commands;

use App\Enums\EmployeeStatus;
use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Notifications\ChronicLateWarning;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * DetectChronicLateCommand — peringatan keterlambatan kronis.
 *
 * Schedule: weeklyOn(Friday, '18:00') di routes/console.php.
 *
 * Logic:
 * 1. Untuk setiap Employee::active(), hitung jumlah late di bulan berjalan
 *    (late_minutes > 0 dari tabel attendances).
 * 2. Threshold default: 3 kali per bulan (configurable via CompanySetting
 *    'chronic_late_threshold').
 * 3. Kalau >= threshold → log + summary di terminal (notification class
 *    di-wire belakangan saat folder Notifications dibuat — sesi terpisah).
 *
 * Output: list employee yang masuk threshold + total count.
 */
class DetectChronicLateCommand extends Command
{
    protected $signature = 'attendance:detect-chronic-late
                            {--month= : Bulan target (YYYY-MM), default bulan ini}';

    protected $description = 'Deteksi karyawan yang terlambat lebih dari N kali dalam bulan berjalan.';

    public function handle(): int
    {
        $targetMonth = $this->option('month')
            ? CarbonImmutable::createFromFormat('Y-m', $this->option('month'))
            : now();

        $threshold = (int) CompanySetting::get('chronic_late_threshold', 3);

        $this->info("Detect Chronic Late untuk: {$targetMonth->format('F Y')}");
        $this->line("Threshold: {$threshold} kali keterlambatan");
        $this->newLine();

        $startOfMonth = $targetMonth->copy()->startOfMonth();
        $endOfMonth = $targetMonth->copy()->endOfMonth();

        $detected = 0;

        Employee::query()
            ->where('status', EmployeeStatus::ACTIVE->value)
            ->whereNull('resign_date')
            ->chunk(100, function ($employees) use ($startOfMonth, $endOfMonth, $threshold, &$detected) {
                foreach ($employees as $employee) {
                    $lateCount = Attendance::where('employee_id', $employee->id)
                        ->whereBetween('date', [
                            $startOfMonth->toDateString(),
                            $endOfMonth->toDateString(),
                        ])
                        ->where('late_minutes', '>', 0)
                        ->count();

                    if ($lateCount >= $threshold) {
                        $this->warn("⚠ {$employee->employee_number} — {$employee->full_name}: {$lateCount}x telat");
                        $detected++;

                        if ($employee->user) {
                            $employee->user->notify(new ChronicLateWarning($employee, $lateCount));
                        }
                    }
                }
            });

        $this->newLine();
        $this->info("Total karyawan dengan chronic late: {$detected}");

        return self::SUCCESS;
    }
}
