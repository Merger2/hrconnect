<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // Prevent MIME sniffing
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // HTTP Strict Transport Security (1 tahun, include subdomains)
        $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');

        // Referrer policy: hanya kirim origin untuk cross-origin
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Content Security Policy (CSP) — broad compatibility dengan Livewire + WebSocket
        $response->headers->set('Content-Security-Policy', implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.bunny.net",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net",
            "font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net",
            "img-src 'self' data: blob: https://c.basemaps.cartocdn.com https://d.basemaps.cartocdn.com https://b.basemaps.cartocdn.com https://a.basemaps.cartocdn.com https://cdnjs.cloudflare.com/ajax/libs/leaflet/",
            "connect-src 'self' ws: wss: https://fonts.googleapis.com https://fonts.bunny.net https://nominatim.openstreetmap.org",
            "frame-ancestors 'self'",
            "form-action 'self'",
            "base-uri 'self'",
        ]));

        return $response;
    }
}