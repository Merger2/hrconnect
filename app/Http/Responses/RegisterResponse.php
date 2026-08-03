<?php

namespace App\Http\Responses;

use Laravel\Fortify\Contracts\RegisterResponse as RegisterResponseContract;

class RegisterResponse implements RegisterResponseContract
{
    public function toResponse($request)
    {
        if ($request->wantsJson()) {
            return response()->json(['status' => 'registered']);
        }

        $user = $request->user();

        if ($user && ! $user->hasVerifiedEmail()) {
            return redirect()->to(route('verification.notice'));
        }

        return redirect()->intended(route('home'));
    }
}
