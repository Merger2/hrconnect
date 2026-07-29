<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureEmployeeDeviceApiAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->group !== 'user') {
            return response()->json([
                'message' => 'Device API is only available for employee accounts.',
            ], 403);
        }

        return $next($request);
    }
}
