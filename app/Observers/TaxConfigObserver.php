<?php

namespace App\Observers;

use App\Models\TaxConfig;
use Illuminate\Support\Facades\Cache;

/**
 * Observer untuk TaxConfig model.
 * Tugas: invalidate cache 'tax_configs' setiap kali ada perubahan
 * (create/update/delete), supaya TaxConfig::cachedAll() selalu return data fresh.
 *
 * Dipasang di AppServiceProvider::boot() (Sesi 10) lewat:
 *     TaxConfig::observe(TaxConfigObserver::class);
 */
class TaxConfigObserver
{
    /**
     * Triggered SETELAH model di-INSERT atau di-UPDATE sukses.
     * `saved` cover both create + update — DRY.
     */
    public function saved(TaxConfig $taxConfig): void
    {
        Cache::forget('tax_configs');
    }

    /**
     * Triggered SETELAH model di-DELETE sukses.
     */
    public function deleted(TaxConfig $taxConfig): void
    {
        Cache::forget('tax_configs');
    }
}
