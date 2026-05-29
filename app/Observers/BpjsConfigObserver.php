<?php

namespace App\Observers;

use App\Models\BpjsConfig;
use Illuminate\Support\Facades\Cache;

/**
 * Observer untuk BpjsConfig model.
 * Tugas: invalidate cache 'bpjs_configs' setiap kali ada perubahan
 * (create/update/delete), supaya BpjsConfig::cachedAll() selalu return data fresh.
 *
 * Dipasang di AppServiceProvider::boot() (Sesi 10) lewat:
 *     BpjsConfig::observe(BpjsConfigObserver::class);
 */
class BpjsConfigObserver
{
    /**
     * Triggered SETELAH model di-INSERT atau di-UPDATE sukses.
     * `saved` cover both create + update — DRY.
     */
    public function saved(BpjsConfig $bpjsConfig): void
    {
        Cache::forget('bpjs_configs');
    }

    /**
     * Triggered SETELAH model di-DELETE sukses.
     */
    public function deleted(BpjsConfig $bpjsConfig): void
    {
        Cache::forget('bpjs_configs');
    }
}
