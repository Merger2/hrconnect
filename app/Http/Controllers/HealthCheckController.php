<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HealthCheckController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $database = $this->databaseOk();
        $storage = is_writable(storage_path('app'));
        $cache = $this->cacheOk();
        $schedulerHeartbeat = Cache::get('health:scheduler_heartbeat_at') ?? Cache::get('scheduler:last_run');

        $checks = [
            'database' => $database,
            'cache' => $cache,
            'storage' => $storage,
            'scheduler_seen' => (bool) $schedulerHeartbeat,
        ];

        return response()->json([
            'status' => in_array(false, $checks, true) ? 'degraded' : 'ok',
            'version' => $this->appVersion(),
            'checks' => $checks,
        ], in_array(false, $checks, true) ? 503 : 200);
    }

    private function databaseOk(): bool
    {
        try {
            DB::select('select 1');

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function cacheOk(): bool
    {
        try {
            $key = 'health:cache_probe';
            Cache::put($key, 'ok', now()->addMinute());

            return Cache::get($key) === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }

    private function appVersion(): string
    {
        $packagePath = base_path('package.json');

        if (! is_file($packagePath)) {
            return (string) config('app.version', 'unknown');
        }

        $package = json_decode((string) file_get_contents($packagePath), true);

        return (string) ($package['version'] ?? config('app.version', 'unknown'));
    }
}
