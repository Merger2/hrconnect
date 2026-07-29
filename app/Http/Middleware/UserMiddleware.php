<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class UserMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Check if the user is authenticated and belongs to the 'user' group
        if ($user?->isUser) {
            return $next($request);
        }

        $context = [
            'path' => $request->path(),
            'route' => $request->route()?->getName(),
            'user_id' => $user?->id,
            'group' => $user?->group,
            'can_access_admin_panel' => $user?->can('accessAdminPanel'),
            'can_view_admin_dashboard' => $user?->can('viewAdminDashboard'),
        ];

        if (config('auth.debug_log', false)) {
            $context['email'] = $user?->email;
            $context['roles'] = $user?->roles()->pluck('slug')->all();
        }

        Log::warning('UserMiddleware denied request.', $context);

        // If the user is not an user, return a 403 Forbidden response
        abort(403);
    }
}
