<?php

namespace App\Providers;

use App\Actions\Jetstream\DeleteUser;
use Illuminate\Support\ServiceProvider;
use Laravel\Jetstream\Jetstream;

class JetstreamServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configurePermissions();

        Jetstream::deleteUsersUsing(DeleteUser::class);

        // CATATAN (2026-08-08): Vite::prefetch(concurrency: 3) sengaja TIDAK dipakai.
        // Prefetch bawaan Laravel men-download SEMUA chunk manifest setelah event
        // 'load' — termasuk vendor-charts/vendor-maps yang sudah di-lazy-load per
        // halaman (app.js ensureCharts/ensureMaps). Hasilnya ~380KB bandwidth
        // terbuang di tiap halaman yang tidak memakai chart/map. Tanpa prefetch,
        // chunk lazy hanya di-download saat halaman benar-benar butuh (lihat
        // resources/js/app.js + scripts/measure-perf.mjs untuk bukti transfer).
    }

    /**
     * Configure the permissions that are available within the application.
     */
    protected function configurePermissions(): void
    {
        Jetstream::defaultApiTokenPermissions(['read']);

        Jetstream::permissions([
            'create',
            'read',
            'update',
            'delete',
        ]);
    }
}
