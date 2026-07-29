<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecurityHeaders
{
    public function __construct(
        private readonly Application $app,
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Core Security Headers
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // HSTS - Force HTTPS (1 year)
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Permissions Policy - Restrict sensitive browser features
        $response->headers->set('Permissions-Policy', 'geolocation=(self), camera=(self), microphone=()');

        // Content Security Policy
        $cspConfig = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data:",
            "img-src 'self' data: blob: https: http:",
            "connect-src 'self' https://tile.openstreetmap.org https://cdn.jsdelivr.net wss:",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        // Allow Vite dev server in local and demo environment
        if ($this->app->environment('local', 'demo')) {
            // Browsers are strict about wildcard ports. We allow common localhost variants
            // and the specific host if we're accessing via LAN IP.
            $host = $request->getHost();

            // Core Vite dev server hosts
            $viteHosts = [
                // localhost
                'http://localhost:5173',
                'ws://localhost:5173',
                'wss://localhost:5173',
                'http://localhost:5174',
                'ws://localhost:5174',
                'wss://localhost:5174',
                // 127.0.0.1
                'http://127.0.0.1:5173',
                'ws://127.0.0.1:5173',
                'wss://127.0.0.1:5173',
                'http://127.0.0.1:5174',
                'ws://127.0.0.1:5174',
                'wss://127.0.0.1:5174',
            ];
            // If accessing via LAN (e.g. 192.168.x.x), allow that IP with port 5173/5174 specifically
            if ($host !== 'localhost' && $host !== '127.0.0.1' && $host !== '[::1]') {
                $viteHosts = array_merge($viteHosts, [
                    "http://{$host}:5173",
                    "ws://{$host}:5173",
                    "wss://{$host}:5173",
                    "http://{$host}:5174",
                    "ws://{$host}:5174",
                    "wss://{$host}:5174",
                ]);
            }

            $localConnectHosts = array_values(array_unique(array_merge(
                $viteHosts,
                $this->localRealtimeConnectHosts($host),
            )));
            $viteHostStr = implode(' ', $viteHosts);
            $localConnectHostStr = implode(' ', $localConnectHosts);

            foreach ($cspConfig as &$directive) {
                if (str_starts_with($directive, 'script-src')) {
                    $directive .= ' '.$viteHostStr;
                }
                if (str_starts_with($directive, 'style-src')) {
                    $directive .= ' '.$viteHostStr;
                }
                if (str_starts_with($directive, 'font-src')) {
                    $directive .= ' '.$viteHostStr;
                }
                if (str_starts_with($directive, 'img-src')) {
                    $directive .= ' '.$viteHostStr;
                }
                if (str_starts_with($directive, 'connect-src')) {
                    $directive .= ' '.$localConnectHostStr;
                }
            }
        }

        $csp = implode('; ', $cspConfig);
        $response->headers->set('Content-Security-Policy', $csp);

        return $response;
    }

    /**
     * @return list<string>
     */
    private function localRealtimeConnectHosts(string $requestHost): array
    {
        $host = (string) config('broadcasting.connections.reverb.options.host', '');
        $configuredPort = (int) config('broadcasting.connections.reverb.options.port', 0);
        $serverPort = (int) config('reverb.servers.reverb.port', 0);

        if ($host === '') {
            return [];
        }

        $hosts = [$host];
        $ports = array_filter(array_unique([
            $configuredPort,
            $serverPort,
            8080,
            8081,
        ]), fn (int $port): bool => $port > 0);

        if ($requestHost !== '' && $requestHost !== '[::1]') {
            $hosts[] = $requestHost;
        }

        if ($host === '127.0.0.1') {
            $hosts[] = 'localhost';
        }

        if ($host === 'localhost') {
            $hosts[] = '127.0.0.1';
        }

        $origins = [];

        foreach (array_unique($hosts) as $candidateHost) {
            foreach ($ports as $port) {
                $origins[] = "ws://{$candidateHost}:{$port}";
                $origins[] = "wss://{$candidateHost}:{$port}";
            }
        }

        return array_values(array_unique($origins));
    }
}
