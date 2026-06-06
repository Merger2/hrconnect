<?php

namespace App\Http\Middleware;

use App\Exceptions\GeofenceViolationException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GeofenceValidation
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! $user->employee || ! $user->employee->branch) {
            return $next($request);
        }

        if ($request->isMethod('post') && $request->has(['latitude', 'longitude'])) {
            $branch = $user->employee->branch;
            $epsilon = 0.0001;

            $branchLat = (float) $branch->latitude;
            $branchLng = (float) $branch->longitude;
            $branchRadius = (float) ($branch->radius ?? 500);
            $userLat = (float) $request->input('latitude');
            $userLng = (float) $request->input('longitude');

            if (abs($branchLat) < $epsilon && abs($branchLng) < $epsilon && $branchRadius < $epsilon) {
                return $next($request);
            }

            $distance = $this->haversine($branchLat, $branchLng, $userLat, $userLng);

            if ($distance > $branchRadius) {
                throw new GeofenceViolationException(
                    "Lokasi Anda ({$distance}m) di luar radius kantor ({$branchRadius}m). "
                );
            }
        }

        return $next($request);
    }

    private function haversine(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
