<?php

namespace App\Console\Commands;

use App\Models\BpjsConfig;
use App\Models\Holiday;
use App\Models\TarifTer;
use Illuminate\Console\Command;

/**
 * WarmCacheCommand — pre-populate cache untuk SSOT data.
 *
 * Schedule: dailyAt('05:00') di routes/console.php (sebelum jam kerja 08:00).
 *
 * Cache yang di-warm:
 * - tarif_ter            (query lookup via DB indexed)
 * - bpjs_configs         (TTL 1 hari) — BpjsConfig::cachedAll()
 * - holidays:{year}      (TTL 1 bulan) — Holiday::cachedYear()
 *
 * Pattern: panggil method SSOT cache yang sudah ada di Model. Side-effect:
 * cache otomatis ter-populate via Cache::remember() di method tersebut.
 */
class WarmCacheCommand extends Command
{
    protected $signature = 'cache:warm
                            {--year= : Tahun untuk warm holiday cache (default: tahun ini + tahun depan)}';

    protected $description = 'Pre-populate cache SSOT (tarif_ter, bpjs_configs, holidays) untuk cold-start mitigation.';

    public function handle(): int
    {
        try {
            $this->info('Warming HRConnect SSOT caches...');
            $this->newLine();

            // 1. Tarif TER (warm dengan query first bracket — DB will cache pages)
            $tarifCount = TarifTer::count();
            $this->line("✓ tarif_ter              ({$tarifCount} entries)");

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
        } catch (\Throwable $e) {
            $this->error("Cache warming gagal: {$e->getMessage()}");
            report($e);

            return self::FAILURE;
        }
    }
}
