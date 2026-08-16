<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Enums\VerificationMethod;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\Shift;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;

/**
 * Seed 30 hari kalender terakhir (hanya hari kerja Senin-Jumat, skip holiday)
 * untuk 50 demo employee dari CompanyEmployeesSeeder (employee{i}@hrconnect.local).
 *
 * Status DETERMINISTIK (hash email+date, bukan random) supaya seed berulang
 * stabil: mayoritas on_time, sebagian late (denda keterlambatan), sedikit
 * absent (denda alpa) — biar demo payroll memperlihatkan potongan.
 *
 * Idempoten: firstOrCreate(employee_id + date). Hari ini di-skip biar user
 * masih bisa Clock In normal. Tanggal sebelum join_date tidak di-seed.
 */
class DemoAttendanceSeeder extends Seeder
{
    public function run(): void
    {
        // Demo/test-only: 30 hari absensi palsu utk user demo — jangan pernah
        // di-seed di production (polusi riwayat absensi nyata + denda).
        // (Guard kedua: DatabaseSeeder juga skip.)
        // Demo VPS (skripsi): SEED_DEMO=true mengizinkan di production — default off.
        if (app()->isProduction() && ! filter_var(env('SEED_DEMO', false), FILTER_VALIDATE_BOOLEAN)) {
            return;
        }

        $defaultShiftId = Shift::where('name', 'Office Hour')->first()?->id;

        $today = CarbonImmutable::today();
        $start = $today->subDays(30);

        // Holiday aktif di range di-fetch SEKALI (pola getHolidaysFlat di ManagesWorkDays trait)
        $holidays = Holiday::where('is_active', true)
            ->whereBetween('date', [$start->toDateString(), $today->toDateString()])
            ->pluck('date')
            ->map(fn ($d) => CarbonImmutable::parse($d)->toDateString())
            ->all();

        $users = User::query()
            ->where('email', 'like', 'employee%@hrconnect.local')
            ->orderBy('email')
            ->get();

        $employees = Employee::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->where('status', EmployeeStatus::ACTIVE->value)
            ->get()
            ->keyBy('user_id');

        $seeded = 0;

        foreach ($users as $user) {
            $employee = $employees->get($user->id);

            if (! $employee) {
                continue;
            }

            $shiftId = $employee->shift_id ?? $defaultShiftId;
            $joinDate = $employee->join_date;

            for ($i = 1; $i <= 30; $i++) {
                $date = $today->subDays($i);

                // Hanya hari kerja Senin-Jumat
                if ($date->isWeekend()) {
                    continue;
                }

                // Skip holiday nasional (pola ManagesWorkDays trait)
                if (in_array($date->toDateString(), $holidays, true)) {
                    continue;
                }

                // Jangan buat absen sebelum employee join
                if ($joinDate && $date->lt($joinDate)) {
                    continue;
                }

                // Status deterministik dari hash email+date (stabil antar re-seed)
                $roll = (crc32($user->email.'|'.$date->toDateString()) & 0x7FFFFFFF) % 100;

                $status = match (true) {
                    $roll < 10 => AttendanceStatus::ABSENT, // ~10%: alpa
                    $roll < 25 => AttendanceStatus::LATE, // ~15%: terlambat
                    default => AttendanceStatus::ON_TIME, // ~75%: tepat waktu
                };

                $clockIn = null;
                $clockOut = null;
                $lateMinutes = 0;

                if ($status === AttendanceStatus::ON_TIME) {
                    $clockIn = $date->setTime(8, 0);
                    $clockOut = $date->setTime(17, 0);
                } elseif ($status === AttendanceStatus::LATE) {
                    $lateMinutes = 30 + ($roll % 31); // 30-60 menit (rata-rata ~08:45)
                    $clockIn = $date->setTime(8, 0)->addMinutes($lateMinutes);
                    $clockOut = $date->setTime(17, 0);
                }
                // ABSENT: tanpa clock_in/clock_out, lat/long tetap null

                Attendance::firstOrCreate(
                    ['employee_id' => $employee->id, 'date' => $date->toDateString()],
                    [
                        'shift_id' => $shiftId,
                        'clock_in' => $clockIn,
                        'clock_out' => $clockOut,
                        'status' => $status,
                        'late_minutes' => $lateMinutes,
                        'is_wfa' => false,
                        'verification_method' => $status === AttendanceStatus::ABSENT
                            ? null
                            : VerificationMethod::FACE_VERIFIED->value,
                    ]
                );

                $seeded++;
            }
        }

        $this->command?->info("DemoAttendanceSeeder: {$seeded} record absensi demo untuk ".$users->count().' karyawan.');
    }
}
