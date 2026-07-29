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

        if ($user && ! $user->hasVerifiedEmail()) {
            return redirect()->to(route('verification.notice'));
        }

        if ($user && ! $user->isAdmin) {
            return redirect()->to(route('home'));
        }

        $intendedUrl = session()->pull('url.intended');
        $ssePath = parse_url(route('sse.notifications'), PHP_URL_PATH);

        if ($intendedUrl && str_contains($intendedUrl, $ssePath)) {
            return redirect()->to(route('admin.dashboard'));
        }

        return $intendedUrl
            ? redirect()->to($intendedUrl)
            : redirect()->to(route('admin.dashboard'));
    }
}
