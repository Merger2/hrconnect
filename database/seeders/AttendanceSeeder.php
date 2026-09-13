<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\VerificationMethod;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Shift;
use Faker\Factory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Seed 1 bulan absensi terakhir (hari kerja Senin-Jumat) untuk semua employee.
     * Mix status: on_time (mayoritas), late, absent (alpa), permission (izin).
     * Hari ini di-skip biar user masih bisa Clock In normal.
     */
    public function run(): void
    {
        $shiftId = Shift::first()?->id;
        $employees = Employee::all();
        $today = Carbon::today();
        $faker = Factory::create(config('app.faker_locale'));
        $seeded = 0;

        foreach ($employees as $employee) {
            // 30 hari ke belakang, skip hari ini + weekend
            for ($i = 1; $i <= 30; $i++) {
                $date = $today->copy()->subDays($i);

                // Skip Sabtu/Minggu
                if ($date->isWeekend()) {
                    continue;
                }

                // Skip kalau sudah ada record (idempoten)
                if (Attendance::where('employee_id', $employee->id)->where('date', $date->toDateString())->exists()) {
                    continue;
                }

                // Tentukan status: 70% on_time, 15% late, 10% absent, 5% permission
                $roll = rand(1, 100);
                $status = match (true) {
                    $roll <= 70 => AttendanceStatus::ON_TIME,
                    $roll <= 85 => AttendanceStatus::LATE,
                    $roll <= 95 => AttendanceStatus::ABSENT,
                    default => AttendanceStatus::PERMISSION,
                };

                $clockIn = null;
                $clockOut = null;
                $lateMinutes = 0;

                if ($status === AttendanceStatus::ON_TIME) {
                    $clockIn = $date->copy()->setTime(8, rand(0, 15));
                    $clockOut = $date->copy()->setTime(17, rand(0, 30));
                } elseif ($status === AttendanceStatus::LATE) {
                    $lateMinutes = rand(5, 90);
                    $clockIn = $date->copy()->setTime(8, 0)->addMinutes($lateMinutes);
                    $clockOut = $date->copy()->setTime(17, rand(0, 30));
                } elseif ($status === AttendanceStatus::PERMISSION) {
                    // Izin: tetap ada clock in/out tapi status permission
                    $clockIn = $date->copy()->setTime(8, rand(0, 30));
                    $clockOut = $date->copy()->setTime(17, rand(0, 30));
                }
                // ABSENT: clock_in & clock_out tetap null

                Attendance::create([
                    'employee_id' => $employee->id,
                    'shift_id' => $shiftId,
                    'date' => $date->toDateString(),
                    'clock_in' => $clockIn,
                    'clock_out' => $clockOut,
                    'lat_in' => $status === AttendanceStatus::ABSENT ? null : $faker->latitude(-6.3, -6.1),
                    'long_in' => $status === AttendanceStatus::ABSENT ? null : $faker->longitude(106.7, 106.9),
                    'lat_out' => $clockOut ? $faker->latitude(-6.3, -6.1) : null,
                    'long_out' => $clockOut ? $faker->longitude(106.7, 106.9) : null,
                    'status' => $status,
                    'late_minutes' => $lateMinutes,
                    'is_wfa' => false,
                    'status_wfa' => null,
                    'verification_method' => $status === AttendanceStatus::ABSENT
                        ? null
                        : VerificationMethod::FACE_VERIFIED->value,
                    'face_similarity_score' => $status === AttendanceStatus::ABSENT
                        ? null
                        : $faker->randomFloat(2, 85, 99),
                ]);

                $seeded++;
            }
        }

        $this->command?->info("AttendanceSeeder: {$seeded} record absensi (1 bulan) untuk ".$employees->count().' karyawan.');
    }
}
