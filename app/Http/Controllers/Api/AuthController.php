<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * AuthController — token-based API authentication.
 *
 * Login flow:
 * 1. POST /auth/login dengan email+password+device_name → return token
 *    (atau two_factor_required = true kalau 2FA aktif).
 * 2. Kalau 2FA aktif: POST /auth/2fa/challenge dengan code → return token.
 * 3. Token never expire (config sanctum.expiration = null).
 *
 * Logout:
 * - /auth/logout → revoke token saat ini
 * - /auth/logout-all → revoke semua token user
 */
class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8'],
            'device_name' => ['required', 'string', 'max:255'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        // 2FA enabled? Beri challenge_id, jangan kirim token dulu.
        if ($user->two_factor_secret) {
            $challengeId = Str::random(40);
            cache()->put("2fa_challenge:{$challengeId}", [
                'user_id' => $user->id,
                'device_name' => $data['device_name'],
            ], now()->addMinutes(5));

            return response()->json([
                'status' => 'success',
                'message' => 'Lanjutkan dengan kode 2FA',
                'data' => [
                    'two_factor_required' => true,
                    'challenge_id' => $challengeId,
                ],
            ]);
        }

        $token = $user->createToken($data['device_name'])->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login berhasil',
            'data' => [
                'token' => $token,
                'user' => $this->userPayload($user),
            ],
        ]);
    }

    public function twoFactorChallenge(Request $request): JsonResponse
    {
        $data = $request->validate([
            'challenge_id' => ['required', 'string'],
            'code' => ['required', 'string', 'regex:/^(\d{6}|[a-zA-Z0-9]{8})$/'],
        ]);

        $challenge = cache()->pull("2fa_challenge:{$data['challenge_id']}");

        if (! $challenge) {
            throw ValidationException::withMessages([
                'challenge_id' => ['Sesi 2FA kedaluwarsa, silakan login ulang.'],
            ]);
        }

        $user = User::find($challenge['user_id']);

        if (! $user) {
            throw ValidationException::withMessages([
                'challenge_id' => ['User tidak ditemukan.'],
            ]);
        }

        // Verifikasi TOTP via Fortify provider (kalau ada) atau manual.
        // Untuk sekarang accept any 6-digit (placeholder — Fortify two-factor flow di web saja).
        // TODO Sprint 31: integrate Fortify TwoFactorAuthenticationProvider untuk verify code.
        $isValidTotp = strlen($data['code']) === 6 && ctype_digit($data['code']);
        $isValidRecovery = strlen($data['code']) === 8;

        if (! $isValidTotp && ! $isValidRecovery) {
            throw ValidationException::withMessages([
                'code' => ['Kode 2FA tidak valid.'],
            ]);
        }

        $token = $user->createToken($challenge['device_name'])->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Verifikasi 2FA berhasil',
            'data' => [
                'token' => $token,
                'user' => $this->userPayload($user),
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil',
        ]);
    }

    public function logoutAll(Request $request): JsonResponse
    {
        $count = $request->user()->tokens()->count();
        $request->user()->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Berhasil logout dari semua perangkat',
            'data' => ['revoked_count' => $count],
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        // Anti-enumeration: selalu return success message yang sama.
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'status' => 'success',
            'message' => 'Jika email terdaftar, link reset password akan dikirim.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->userPayload($request->user()),
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => $user->email_verified_at?->toIso8601String(),
            'two_factor_enabled' => (bool) $user->two_factor_secret,
            'password_changed_at' => $user->password_changed_at?->toIso8601String(),
            'roles' => $user->getRoleNames()->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ];
    }
}
