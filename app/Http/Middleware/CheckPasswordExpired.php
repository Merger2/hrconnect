<?php

namespace App\Http\Middleware;

use App\Models\CompanySetting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckPasswordExpired — enforce password expiry policy (CAT-005).
 *
 * Default: 90 hari (configurable via CompanySetting key 'password_expiry_days').
 *
 * Logic:
 * 1. Skip kalau user guest (belum login)
 * 2. Skip kalau user belum punya password_changed_at (legacy/akun manual)
 * 3. Skip kalau halaman saat ini sudah di whitelist (cegah redirect loop)
 * 4. Hitung selisih hari sejak password_changed_at
 * 5. Kalau > expiry days → redirect ke profile.show dengan flash warning
 */
class CheckPasswordExpired
{
    /**
     * Route names yang TIDAK di-block oleh middleware ini.
     * Cegah redirect loop saat user sedang ganti password.
     */
    private const WHITELIST_ROUTES = [
        'logout',
        'password.update',
        'password.confirm',
        'password.confirm.store',
        'password.confirmation',
        'password.email',
        'password.request',
        'password.reset',
        'profile.show',
        'knowledge-base.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Tier 1: skip kalau guest
        if (! $user) {
            return $next($request);
        }

        // Tier 2: skip kalau halaman saat ini di whitelist
        $currentRoute = $request->route()?->getName();
        if ($currentRoute) {
            foreach (self::WHITELIST_ROUTES as $pattern) {
                if (Str::is($pattern, $currentRoute)) {
                    return $next($request);
                }
            }
        }

        // Tier 3: force redirect kalau user belum punya password_changed_at
        // (User baru dari EmployeeController@store — harus ganti password dulu)
        if (! $user->password_changed_at) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Anda harus mengganti password sebelum dapat melanjutkan.',
                ], 403);
            }

            return redirect()
                ->route('profile.show')
                ->with('warning', 'Ini pertama kali Anda login. Silakan ganti password sekarang.');
        }

        // Tier 4: hitung expiry. Default 90 hari, configurable via CompanySetting.
        $expiryDays = (int) CompanySetting::get('password_expiry_days', 90);
        $expiresAt = $user->password_changed_at->copy()->addDays($expiryDays);

        if (now()->lessThanOrEqualTo($expiresAt)) {
            return $next($request);
        }

        // Expired → redirect ke profile.show dengan warning
        return redirect()
            ->route('profile.show')
            ->with('warning', "Password Anda sudah kedaluwarsa (lebih dari {$expiryDays} hari). Silakan ganti password sekarang demi keamanan akun.");
    }
}
