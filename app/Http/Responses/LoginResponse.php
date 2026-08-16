<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();
        $intendedUrl = session()->pull('url.intended');

        if ($user && ! $user->hasVerifiedEmail()) {
            // Let a user resume an email-verification flow they started before
            // logging in (e.g. clicked a verification link while a guest).
            if ($intendedUrl && str_contains($intendedUrl, '/email/verify')) {
                return redirect()->to($intendedUrl);
            }

            return redirect()->to(route('verification.notice'));
        }

        if ($user && ! $user->isAdmin) {
            return redirect()->to(route('home'));
        }

        $ssePath = parse_url(route('sse.notifications'), PHP_URL_PATH);

        if ($intendedUrl && str_contains($intendedUrl, $ssePath)) {
            return redirect()->to(route('admin.dashboard'));
        }

        return $intendedUrl
            ? redirect()->to($intendedUrl)
            : redirect()->to(route('admin.dashboard'));
    }
}
