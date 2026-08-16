<?php

namespace App\Actions;

use App\Models\User;
use App\Support\ActiveSessionGuard;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthenticateLoginAttempt
{
    public function __construct(
        protected ActiveSessionGuard $activeSessionGuard
    ) {}

    public function __invoke(Request $request): ?User
    {
        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            $user = User::where('email', $request->email)->first();
        } else {
            $user = User::where('phone', $request->email)->first();
        }

        if ($user && Hash::check($request->password, $user->password) && $user->canAuthenticate()) {
            // Multi-device diizinkan secara default; blokir hanya jika
            // AUTH_SINGLE_DEVICE=true (keputusan Fikih 2026-08-16).
            if (config('auth.single_device') && $this->activeSessionGuard->hasActiveSession($user)) {
                throw ValidationException::withMessages([
                    'email' => __('This account is still active on another device. Please log out from that device first.'),
                ])->redirectTo('/login');
            }

            return $user;
        }

        return null;
    }
}
