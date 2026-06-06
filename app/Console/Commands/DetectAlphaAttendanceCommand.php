<?php

namespace App\Console\Commands;

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\RequestStatus;
use App\Enums\VerificationMethod;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Leave;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

/**
 * DetectAlphaAttendanceCommand — deteksi karyawan alpha (tidak hadir tanpa keterangan).
 *
 * Schedule: dailyAt('23:59') di routes/console.php.
 *
 * Logic per hari:
 * 1. Skip kalau weekend (Sabtu/Minggu)
 * 2. Skip kalau holiday di tabel holidays
 * 3. Untuk setiap Employee::active():
 *    - Skip kalau sudah ada Attendance record hari ini (clock-in atau status absent sebelumnya)
 *    - Skip kalau ada Leave approved yang cover tanggal ini (mode flexible: range start_date..end_date)
 *    - Selain itu → buat Attendance dengan status=ABSENT, verification_method=MANUAL
 *
 * Output:
 * - INFO log per alpha detected
 * - Total count + summary di terminal
 */
class DetectAlphaAttendanceCommand extends Command
{
    protected $signature = 'attendance:detect-alpha
                            {--date= : Tanggal target (YYYY-MM-DD), default hari ini}';

    protected $description = 'Deteksi karyawan yang tidak hadir tanpa keterangan dan tandai sebagai alpha (status=ABSENT).';

    public function handle(): int
    {
        $targetDate = $this->option('date')
            ? CarbonImmutable::parse($this->option('date'))->startOfDay()
            : now()->startOfDay();

        $this->info("Detect Alpha untuk tanggal: {$targetDate->toDateString()}");

        // Skip weekend (Sabtu/Minggu)
        if ($targetDate->isWeekend()) {
            $this->warn('Tanggal target adalah weekend — skip.');

            return self::SUCCESS;
        }

        // Skip holiday
        if (Holiday::isHoliday($targetDate)) {
            $this->warn('Tanggal target adalah holiday — skip.');

            return self::SUCCESS;
        }

        $createdCount = 0;
        $skippedAttendance = 0;
        $skippedLeave = 0;

        Employee::query()
            ->where('status', EmployeeStatus::ACTIVE->value)
            ->whereNull('resign_date')
            ->chunk(100, function ($employees) use ($targetDate, &$createdCount, &$skippedAttendance, &$skippedLeave) {
                foreach ($employees as $employee) {
                    // Cek sudah ada attendance record hari ini
                    // whereDate() pakai DATE() function di SQL untuk handle baik
                    // 'YYYY-MM-DD' string maupun 'YYYY-MM-DD HH:MM:SS' timestamp.
                    $hasAttendance = Attendance::where('employee_id', $employee->id)
                        ->whereDate('date', $targetDate->toDateString())
                        ->exists();

                    if ($hasAttendance) {
                        $skippedAttendance++;

                        continue;
                    }

                    // Cek ada leave approved yang cover tanggal ini
                    $hasApprovedLeave = Leave::where('employee_id', $employee->id)
                        ->where('status', RequestStatus::APPROVED)
                        ->whereDate('start_date', '<=', $targetDate->toDateString())
                        ->whereDate('end_date', '>=', $targetDate->toDateString())
                        ->exists();

                    if ($hasApprovedLeave) {
                        $skippedLeave++;

                        continue;
                    }

                    // Tidak hadir tanpa keterangan → buat record alpha
                    Attendance::create([
                        'employee_id' => $employee->id,
                        'shift_id' => $employee->shift_id,
                        'date' => $targetDate->toDateString(),
                        'status' => AttendanceStatus::ABSENT,
                        'verification_method' => VerificationMethod::MANUAL->value,
                        'exception_notes' => 'Auto-detected alpha (no clock-in, no approved leave).',
                    ]);

                    $createdCount++;
                }
            });

        $this->newLine();
        $this->info("Selesai. Alpha terdeteksi: {$createdCount} karyawan.");
        $this->line("Skip (sudah ada attendance): {$skippedAttendance}");
        $this->line("Skip (cuti approved): {$skippedLeave}");

        return self::SUCCESS;
    }
}
