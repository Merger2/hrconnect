<?php

namespace App\Observers;

use App\Models\Holiday;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Observer untuk Holiday model.
 * Tugas: invalidate cache 'holidays:{year}' setiap kali ada perubahan
 * (create/update/delete), supaya Holiday::cachedYear() selalu return data fresh.
 *
 * Edge case: kalau tanggal holiday di-edit cross-year (misal 2026-12-31 → 2027-01-01),
 * kedua cache key (holidays:2026 dan holidays:2027) di-invalidate.
 *
 * Dipasang di AppServiceProvider::boot() (Sesi 10) lewat:
 *     Holiday::observe(HolidayObserver::class);
 */
class HolidayObserver
{
    /**
     * Triggered SETELAH model di-INSERT atau di-UPDATE sukses.
     */
    public function saved(Holiday $holiday): void
    {
        $this->invalidateCache($holiday);
    }

    /**
     * Triggered SETELAH model di-DELETE sukses.
     */
    public function deleted(Holiday $holiday): void
    {
        $this->invalidateCache($holiday);
    }

    /**
     * Invalidate cache 'holidays:{year}' untuk tahun terkait.
     * Kalau date berubah cross-year (saat update), invalidate 2 tahun.
     */
    private function invalidateCache(Holiday $holiday): void
    {
        $currentYear = $this->extractYear($holiday->date);
        Cache::forget("holidays:{$currentYear}");

        // Edge case: date di-edit ke tahun berbeda → invalidate juga tahun lama
        if ($holiday->isDirty('date') && $holiday->getOriginal('date') !== null) {
            $originalYear = $this->extractYear($holiday->getOriginal('date'));
            if ($originalYear !== $currentYear) {
                Cache::forget("holidays:{$originalYear}");
            }
        }
    }

    /**
     * Ambil tahun dari nilai date.
     * Defensive: kalau date Carbon, pakai ->year. Kalau raw string, parse manual.
     */
    private function extractYear(mixed $date): int
    {
        if ($date instanceof Carbon) {
            return $date->year;
        }

        return (int) substr((string) $date, 0, 4);
    }
}
