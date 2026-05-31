<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * HealthController — system health check.
 *
 * Public endpoint untuk monitoring + cron warm-up (cegah Neon cold start).
 * Return 200 kalau semua service hijau, 503 kalau ada yang down.
 */
class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $services = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
            'queue' => $this->checkQueue(),
            'storage' => $this->checkStorage(),
        ];

        $allUp = ! in_array('down', $services, true);

        return response()->json([
            'status' => $allUp ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
            'services' => $services,
        ], $allUp ? 200 : 503);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'connected';
        } catch (Throwable) {
            return 'down';
        }
    }

    private function checkCache(): string
    {
        try {
            Cache::put('health_check', '1', 5);

            return Cache::get('health_check') === '1' ? 'connected' : 'down';
        } catch (Throwable) {
            return 'down';
        }
    }

    private function checkQueue(): string
    {
        try {
            // queue=database driver → cek tabel jobs ada
            DB::table('jobs')->count();

            return 'connected';
        } catch (Throwable) {
            return 'down';
        }
    }

    private function checkStorage(): string
    {
        try {
            return Storage::disk('local')->exists('') ? 'writable' : 'down';
        } catch (Throwable) {
            return 'down';
        }
    }
}
