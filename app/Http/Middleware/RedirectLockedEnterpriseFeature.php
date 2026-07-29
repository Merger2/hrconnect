<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectLockedEnterpriseFeature
{
    public function handle(Request $request, Closure $next, string $feature, string $fallback, string $redirectRoute): Response
    {
        return $next($request);
    }
}
