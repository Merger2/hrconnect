<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLockedEnterpriseFeature
{
    public function handle(Request $request, Closure $next, string $feature, string $fallback, string $redirectRoute): Response
    {
        // Temuan K3 AUDIT-2026-08-04: middleware sebelumnya NO-OP total (return $next)
        // padahal dipakai 29× di routes. Implementasi: jika Setting `feature.<name>`
        // bernilai truthy (mis. '1'/'locked'), redirect ke redirectRoute.
        // Tanpa setting = unlock → perilaku sama dengan sebelumnya (aman).
        $locked = Setting::getValue("feature.{$feature}", false);

        if ($locked && ! in_array(strtolower((string) $locked), ['', '0', 'false', 'off', 'no'], true)) {
            return redirect()->route($redirectRoute);
        }

        return $next($request);
    }
}
