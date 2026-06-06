<?php

namespace App\Observers;

use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Observer untuk Attendance model.
 * Tugas: invalidate cache attendance setiap kali ada perubahan
 * (create/update/delete), supaya cache attendance:today:{employee_id} dan
 * attendance:monthly:{employee_id}:{period} selalu fresh.
 *
 * NOTE: Cache attendance saat ini BELUM dipakai di service manapun.
 * Observer ini dibuat sebagai infrastructure persiapan untuk fitur
 * dashboard real-time attendance (Hari 4-5 atau V2).
 *
 * Cache keys yang di-invalidate:
 * - attendance:today:{employee_id} — attendance hari ini per karyawan
 * - attendance:monthly:{employee_id}:{period} — rekap bulanan (period = YYYY-MM)
 *
 * Dipasang di AppServiceProvider::boot() (Sesi 10) lewat:
 *     Attendance::observe(AttendanceObserver::class);
 */
class AttendanceObserver
{
    /**
     * Triggered SETELAH model di-INSERT atau di-UPDATE sukses.
     */
    public function saved(Attendance $attendance): void
    {
        $this->invalidateCache($attendance);
    }

    /**
     * Triggered SETELAH model di-DELETE sukses.
     */
    public function deleted(Attendance $attendance): void
    {
        $this->invalidateCache($attendance);
    }

    /**
     * Invalidate 2 cache keys terkait attendance ini.
     */
    private function invalidateCache(Attendance $attendance): void
    {
        $employeeId = $attendance->employee_id;
        $period = $this->extractPeriod($attendance->date);

        Cache::forget("attendance:today:{$employeeId}");
        Cache::forget("attendance:monthly:{$employeeId}:{$period}");
    }

    /**
     * Ambil period (YYYY-MM) dari nilai date.
     * Defensive: kalau Carbon pakai format(), kalau raw string pakai substr.
     */
    private function extractPeriod(mixed $date): string
    {
        if ($date instanceof Carbon) {
            return $date->format('Y-m');
        }

        return substr((string) $date, 0, 7);
    }
}
