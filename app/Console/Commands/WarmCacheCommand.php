<?php

namespace App\Console\Commands;

use App\Models\BpjsConfig;
use App\Models\Holiday;
use App\Models\TaxConfig;
use Illuminate\Console\Command;

/**
 * WarmCacheCommand — pre-populate cache untuk SSOT data.
 *
 * Schedule: dailyAt('05:00') di routes/console.php (sebelum jam kerja 08:00).
 *
 * Cache yang di-warm (lihat caching-strategy.md + Hari 2 Sesi 1-3):
 * - tax_configs       (TTL 1 hari)  — TaxConfig::cachedAll()
 * - bpjs_configs      (TTL 1 hari)  — BpjsConfig::cachedAll()
 * - holidays:{year}   (TTL 1 bulan) — Holiday::cachedYear()
 *
 * Pattern: panggil method SSOT cache yang sudah ada di Model. Side-effect:
 * cache otomatis ter-populate via Cache::remember() di method tersebut.
 *
 * Catatan: warm cache opsional. Cache invalidation primer di-handle observer
 * (TaxConfigObserver, BpjsConfigObserver, HolidayObserver) saat data diubah.
 * Command ini hanya untuk cold-start scenario (deploy fresh, restart server).
 */
class WarmCacheCommand extends Command
{
    protected $signature = 'cache:warm
                            {--year= : Tahun untuk warm holiday cache (default: tahun ini + tahun depan)}';

    protected $description = 'Pre-populate cache SSOT (tax_configs, bpjs_configs, holidays) untuk cold-start mitigation.';

    public function handle(): int
    {
        $this->info('Warming HRConnect SSOT caches...');
        $this->newLine();

        // 1. Tax Configs (TER A/B/C)
        $taxCount = count(TaxConfig::cachedAll());
        $this->line("✓ tax_configs            ({$taxCount} entries)");

        // 2. BPJS Configs (Kesehatan, JHT, JP, JKK, JKM)
        $bpjsCount = count(BpjsConfig::cachedAll());
        $this->line("✓ bpjs_configs           ({$bpjsCount} entries)");

        // 3. Holidays — warm tahun ini + tahun depan supaya transisi akhir tahun mulus.
        $currentYear = $this->option('year') ? (int) $this->option('year') : now()->year;
        $nextYear = $currentYear + 1;

        $thisYearCount = count(Holiday::cachedYear($currentYear));
        $this->line("✓ holidays:{$currentYear}        ({$thisYearCount} entries)");

        if (! $this->option('year')) {
            $nextYearCount = count(Holiday::cachedYear($nextYear));
            $this->line("✓ holidays:{$nextYear}        ({$nextYearCount} entries)");
        }

        $this->newLine();
        $this->info('Cache warming selesai.');

        return self::SUCCESS;
    }
}
