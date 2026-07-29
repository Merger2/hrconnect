<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

class AuthDebugController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        if (! app()->environment(['local', 'testing']) && ! config('app.debug')) {
            abort(404);
        }

        $user = $request->user();
        $adminDashboardRoute = Route::getRoutes()->getByName('admin.dashboard');
        $payload = [
            'authenticated' => $user !== null,
            'id' => $user?->id,
            'email' => $user?->email,
            'group' => $user?->group,
            'roles' => $user?->roles()->get(['roles.id', 'name', 'slug', 'permission_keys', 'is_super_admin'])
                ->map(fn ($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                    'is_super_admin' => (bool) $role->is_super_admin,
                    'permission_count' => count($role->permission_keys ?? []),
                    'has_dashboard_permission' => in_array('admin.dashboard.view', $role->permission_keys ?? [], true),
                ])
                ->all() ?? [],
            'is_admin' => $user?->isAdmin,
            'is_user' => $user?->isUser,
            'can_access_admin_panel' => $user?->can('accessAdminPanel'),
            'can_view_admin_dashboard' => $user?->can('viewAdminDashboard'),
            'preferred_home_url' => $user?->preferredHomeUrl(),
            'session_id' => $request->session()->getId(),
            'intended_url' => $request->session()->get('url.intended'),
            'admin_dashboard_route' => [
                'uri' => $adminDashboardRoute?->uri(),
                'name' => $adminDashboardRoute?->getName(),
                'middleware' => $adminDashboardRoute?->gatherMiddleware() ?? [],
            ],
            'app' => [
                'env' => app()->environment(),
                'debug' => (bool) config('app.debug'),
                'url' => config('app.url'),
                'base_path' => base_path(),
            ],
        ];

        Log::info('Auth debug endpoint viewed.', $payload);

        return response()->json($payload)->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }
}
