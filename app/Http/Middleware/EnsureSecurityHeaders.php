<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensure security headers on every response.
 *
 * Pattern adapted from PasPapan (app/Http/Middleware/EnsureSecurityHeaders.php).
 * PasPapan is the only reference repo implementing full security headers.
 *
 * - CSP: restrict script/style/font/image/connect sources
 * - HSTS: enforce HTTPS for 1 year (production only)
 * - X-Frame-Options: prevent clickjacking
 * - X-Content-Type-Options: prevent MIME sniffing
 * - Referrer-Policy: control referrer leakage
 * - Permissions-Policy: restrict browser features (camera/geolocation)
 */
class EnsureSecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $this->addSecurityHeaders($request, $response);

        return $response;
    }

    private function addSecurityHeaders(Request $request, Response $response): void
    {
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(self), geolocation=(self), microphone=()');

        if (app()->isProduction() && $request->secure()) {
            $response->headers->set(
                'Strict-Transport-Security',
                'max-age=31536000; includeSubDomains'
            );
        }

        $response->headers->set('Content-Security-Policy', $this->buildCsp($request));
    }

    /**
     * Build Content-Security-Policy header value.
     *
     * Directives explained:
     * - default-src 'self': baseline restriction
     * - script-src: Vite HMR (dev), Livewire inline, face-api.js, SweetAlert2
     * - style-src: Tailwind v4 (uses inline styles in dev), Google Fonts
     * - font-src: Google Fonts + Material Symbols
     * - img-src: employee photos (blob: for face-api), Leaflet tiles
     * - connect-src: API calls, Vite HMR (dev), SSE streaming (RAG), Leaflet tiles, WebSocket
     * - frame-ancestors: prevent embedding
     * - base-uri + form-action: prevent injection
     */
    private function buildCsp(Request $request): string
    {
        $csp = [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob:",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com",
            "font-src 'self' https://fonts.gstatic.com data:",
            "img-src 'self' data: blob: https:",
            "connect-src 'self' https://tile.openstreetmap.org ws: wss:",
            "frame-ancestors 'self'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        if (! app()->isProduction()) {
            $csp[] = "script-src 'self' 'unsafe-inline' 'unsafe-eval' blob: http://localhost:*";
            $csp[] = "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com http://localhost:*";
            $csp[] = "font-src 'self' https://fonts.gstatic.com data: http://localhost:*";
            $csp[] = "img-src 'self' data: blob: https: http://localhost:*";
            $csp[] = "connect-src 'self' https://tile.openstreetmap.org ws: wss: http://localhost:* ws://localhost:*";
        }

        return implode('; ', array_unique($csp));
    }
}
