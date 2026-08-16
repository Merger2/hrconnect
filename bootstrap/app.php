<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckPasswordExpired;
use App\Http\Middleware\EnsureSecurityHeaders;
use App\Http\Middleware\RedirectLockedEnterpriseFeature;
use App\Http\Middleware\UserMiddleware;
use App\Http\Middleware\VerifyAttendanceIntegrationSignature;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckAbilities;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        apiPrefix: 'api/v1',
        then: function () {
            $moduleRoutes = ['knowledge-base'];
            foreach ($moduleRoutes as $module) {
                $path = base_path("routes/{$module}.php");
                if (file_exists($path)) {
                    Route::middleware('web')->group($path);
                }
            }

            if (file_exists(base_path('routes/jetstream.php'))) {
                Route::middleware('web')->group(base_path('routes/jetstream.php'));
            }
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Sanctum SPA stateful (cookie-based) untuk same-origin requests.
        // PWA mobile akan pakai Bearer token (auth:sanctum guard) — tidak butuh stateful.
        $middleware->statefulApi();

        // Trust all proxies (cloud/LB agnostic). Sempitkan ke IP spesifik jika tahu.
        $middleware->trustProxies(at: '*');

        // Proteksi Host header poisoning.
        $middleware->trustHosts(at: fn () => [config('app.url')]);

        // CSRF exception for MCP browser logger (external debug tool).
        $middleware->validateCsrfTokens(except: [
            '_boost/browser-logs',
        ]);

        // Security headers: CSP, HSTS, X-Frame-Options (PasPapan pattern).
        $middleware->web(append: [
            EnsureSecurityHeaders::class,
        ]);

        // Aliases shortcut untuk middleware Spatie & Sanctum (digunakan di routes).
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'password.expired' => CheckPasswordExpired::class,
            'attendance.integration.signature' => VerifyAttendanceIntegrationSignature::class,
            'user' => UserMiddleware::class,
            'admin' => AdminMiddleware::class,
            'feature.lock' => RedirectLockedEnterpriseFeature::class,
            // Sanctum v4 menghapus auto-registration alias middleware — wajib manual.
            'abilities' => CheckAbilities::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withBroadcasting(__DIR__.'/../routes/channels.php')
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*') || $request->expectsJson();
        });

        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Resource tidak ditemukan.',
                ], 404);
            }
        });

        $exceptions->render(function (HttpException $e, Request $request) {
            if (($request->is('api/*') || $request->expectsJson()) && in_array($e->getStatusCode(), [429, 403])) {
                return response()->json([
                    'status' => 'error',
                    'message' => $e->getMessage() ?: 'Terjadi kesalahan.',
                ], $e->getStatusCode());
            }
        });
    })->create();
