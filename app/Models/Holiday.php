<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

#[Fillable(['date', 'name', 'is_active'])]
class Holiday extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Ambil semua tanggal holiday di tahun tertentu dari cache.
     * Plain array berisi string tanggal format Y-m-d.
     *
     * Cache disimpan 1 bulan (TTL panjang karena holiday jarang berubah).
     * Akan di-invalidate otomatis lewat HolidayObserver (Sesi 7).
     *
     * cache entry per tahun (BUKAN per tanggal seperti pattern lama
     * "holiday_{date}"). Hemat 30+ entries per payroll generation.
     *
     * @return array<int, string>
     */
    public static function cachedYear(int $year): array
    {
        return Cache::remember(
            "holidays:{$year}",
            now()->addMonth(),
            fn () => static::query()
                ->whereYear('date', $year)
                ->where('is_active', true)
                ->pluck('date')
                ->map(fn ($d) => $d instanceof Carbon
                    ? $d->format('Y-m-d')
                    : substr((string) $d, 0, 10))
                ->toArray()
        );
    }

    /**
     * Cek apakah tanggal adalah holiday.
     * Internal pakai cachedYear() — sekali query per tahun, lalu in_array() di memory.
     */
    public static function isHoliday(Carbon $date): bool
    {
        return in_array(
            $date->toDateString(),
            static::cachedYear($date->year),
            true
        );
    }
}
