<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMaintenanceMode
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->can('accessAdminPanel')) {
            return $next($request);
        }

        if ($request->is([
            'login',
            'logout',
            'register',
            'forgot-password',
            'reset-password*',
            'email/verification*',
        ])) {
            return $next($request);
        }

        if (Setting::getValue('app.maintenance_mode', 0) == 1) {
            abort(503, 'System is currently under maintenance. Please try again later.');
        }

        return $next($request);
    }
}
