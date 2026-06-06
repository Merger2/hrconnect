<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DeviceDetection
{
    public function handle(Request $request, Closure $next): Response
    {
        $userAgent = $request->userAgent() ?? '';
        $deviceType = $this->detectDeviceType($userAgent);
        $browser = $this->detectBrowser($userAgent);
        $os = $this->detectOs($userAgent);
        $isMobile = $deviceType === 'mobile';

        $request->attributes->set('device_type', $deviceType);
        $request->attributes->set('browser', $browser);
        $request->attributes->set('os', $os);
        $request->attributes->set('is_mobile', $isMobile);

        return $next($request);
    }

    private function detectDeviceType(string $ua): string
    {
        if (preg_match('/Mobile|Android|iPhone|iPod|Opera Mini|IEMobile|WPDesktop/i', $ua)) {
            return 'mobile';
        }

        if (preg_match('/Tablet|iPad|Android(?!.*Mobile)/i', $ua)) {
            return 'tablet';
        }

        return 'desktop';
    }

    private function detectBrowser(string $ua): string
    {
        if (str_contains($ua, 'Edg/')) {
            return 'edge';
        }
        if (str_contains($ua, 'Firefox/')) {
            return 'firefox';
        }
        if (str_contains($ua, 'OPR/') || str_contains($ua, 'Opera/')) {
            return 'opera';
        }
        if (str_contains($ua, 'Chrome/')) {
            return 'chrome';
        }
        if (str_contains($ua, 'Safari/')) {
            return 'safari';
        }

        return 'unknown';
    }

    private function detectOs(string $ua): string
    {
        if (preg_match('/Windows NT/', $ua)) {
            return 'windows';
        }
        if (preg_match('/Mac OS X/', $ua)) {
            return 'mac';
        }
        if (preg_match('/Linux/', $ua)) {
            return 'linux';
        }
        if (preg_match('/Android/', $ua)) {
            return 'android';
        }
        if (preg_match('/iPhone|iPad|iPod/', $ua)) {
            return 'ios';
        }

        return 'unknown';
    }
}
