<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\TwoFactorLoginResponse as TwoFactorLoginResponseContract;

/**
 * Response setelah two-factor challenge sukses.
 *
 * Logika redirect identik dengan LoginResponse (non-admin → home, admin →
 * admin dashboard, resume email-verification) — di-extend dari LoginResponse
 * supaya tidak duplikat dan tidak drif saat logika redirect berubah. Fortify
 * default memakai redirect()->intended(Fortify::redirects('login')) yang
 * fallback ke fortify.home = /dashboard (halaman admin) → 403 untuk
 * non-admin, jadi override ini wajib.
 */
class TwoFactorLoginResponse extends LoginResponse implements TwoFactorLoginResponseContract {}
