<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class VercelMaintenanceController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! config('services.vercel.maintenance_endpoint_enabled', false)) {
            Log::warning('Vercel maintenance endpoint rejected.', [
                'reason' => 'endpoint_disabled',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            abort(404);
        }

        $expectedToken = (string) config('services.vercel.maintenance_token', '');
        $providedToken = (string) $request->input('token', '');
        $seedRequested = $request->boolean('seed');

        if ($expectedToken === '' || ! hash_equals($expectedToken, $providedToken)) {
            Log::warning('Vercel maintenance endpoint rejected.', [
                'reason' => $expectedToken === '' ? 'missing_configured_token' : 'invalid_token',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            abort(404);
        }

        if ($seedRequested && ! config('services.vercel.allow_web_seed', false)) {
            Log::warning('Vercel maintenance endpoint rejected.', [
                'reason' => 'seed_not_allowed',
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            abort(403);
        }

        Log::info('Vercel maintenance migration started.', [
            'seed' => $seedRequested,
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        $migrateExitCode = Artisan::call('migrate', ['--force' => true]);
        $migrateOutput = Artisan::output();

        $seedExitCode = null;
        $seedOutput = null;

        if ($seedRequested) {
            $seedExitCode = Artisan::call('db:seed', ['--force' => true]);
            $seedOutput = Artisan::output();
        }

        $ok = $migrateExitCode === 0 && ($seedExitCode === null || $seedExitCode === 0);

        Log::info('Vercel maintenance migration finished.', [
            'ok' => $ok,
            'seed' => $seedRequested,
            'migrate_exit_code' => $migrateExitCode,
            'seed_exit_code' => $seedExitCode,
        ]);

        $payload = [
            'ok' => $ok,
            'migrate_exit_code' => $migrateExitCode,
            'seed_exit_code' => $seedExitCode,
        ];

        if (! app()->environment('production')) {
            $payload['migrate_output'] = $migrateOutput;
            $payload['seed_output'] = $seedOutput;
        }

        return response()->json($payload, $ok ? 200 : 500);
    }
}
