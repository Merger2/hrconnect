<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {

        // Check if user has admin access via roles or permissions
        $user = $request->user();
        $context = [
            'path' => $request->path(),
            'route' => $request->route()?->getName(),
            'user_id' => $user?->id,
            'group' => $user?->group,
            'is_admin' => $user?->isAdmin,
            'can_access_admin_panel' => $user?->can('accessAdminPanel'),
            'can_view_admin_dashboard' => $user?->can('viewAdminDashboard'),
        ];

        // Super-admin role
        if (config('auth.debug_log', false)) {
            $context['email'] = $user?->email;
            $context['roles'] = $user?->roles()->pluck('slug')->all() ?? [];
        }

        Log::info('AdminMiddleware checked request.', $context);

        if ($user?->can('accessAdminPanel')) {
            $response = $next($request);

            Log::info('AdminMiddleware completed request.', [
                ...$context,
                'response_status' => $response->getStatusCode(),
            ]);

            return $response;
        }

        // Check for admin-like permissions
        $adminPermissions = [
            'accessAdminPanel',
            'manage_employees',
            'view_attendances',
            'view_payrolls',
            'view_assets',
            'view_reimbursements',
            'view_admin_document_requests',
            'manage_attendance_corrections',
        ];

        foreach ($adminPermissions as $perm) {
            if ($user->can($perm)) {
                return $next($request);
            }
        }
        Log::warning('AdminMiddleware denied request.', $context);

        abort(403, 'Akses admin diperlukan.');
    }
}
