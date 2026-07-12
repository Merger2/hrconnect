<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ForgotPasswordRequest;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\TwoFactorChallengeRequest;
use App\Models\User;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Fortify\Fortify;
use Laravel\Sanctum\TransientToken;
use Throwable;

#[Group('Auth')]
class AuthController extends Controller
{
    public function __construct(
        private readonly TwoFactorAuthenticationProvider $twoFactorProvider,
    ) {}

    #[Endpoint(title: 'Login', description: 'Authenticate user with email/password. Returns Bearer token or 2FA challenge. Flow: Auth — Login → (2FA jika perlu) → Get Profile.')]
    #[BodyParameter(name: 'email', description: 'User email address', required: true, type: 'string')]
    #[BodyParameter(name: 'password', description: 'User password (min 8 chars)', required: true, type: 'string')]
    #[BodyParameter(name: 'device_name', description: 'Device identifier for the token', required: true, type: 'string')]
    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password salah.'],
            ]);
        }

        if ($user instanceof MustVerifyEmail && ! $user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email belum diverifikasi. Silakan cek email Anda untuk link verifikasi.',
            ], 403);
        }

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

    #[Endpoint(title: '2FA Challenge', description: 'Complete two-factor authentication with TOTP or recovery code. Flow: Auth (2FA step) — setelah Login jika 2FA aktif.')]
    #[BodyParameter(name: 'challenge_id', description: 'Challenge ID from login response', required: true, type: 'string')]
    #[BodyParameter(name: 'code', description: '6-digit TOTP code or 8-char recovery code', required: true, type: 'string')]
    public function twoFactorChallenge(TwoFactorChallengeRequest $request): JsonResponse
    {
        $data = $request->validated();

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

        $validRecoveryCode = collect($user->recoveryCodes())->first(
            fn (string $recoveryCode): bool => hash_equals($recoveryCode, $data['code'])
        );

        $isValidTotp = false;

        if (! $validRecoveryCode) {
            try {
                $isValidTotp = $this->twoFactorProvider->verify(
                    Fortify::currentEncrypter()->decrypt($user->two_factor_secret),
                    $data['code']
                );
            } catch (Throwable) {
                $isValidTotp = false;
            }
        }

        if (! $isValidTotp && ! $validRecoveryCode) {
            throw ValidationException::withMessages([
                'code' => ['Kode 2FA tidak valid.'],
            ]);
        }

        if ($validRecoveryCode) {
            $user->replaceRecoveryCode($validRecoveryCode);
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

    #[Endpoint(title: 'Logout', description: 'Revoke current Bearer token. Flow: Auth (logout device).')]
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        if ($token && ! $token instanceof TransientToken) {
            $token->delete();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Logout berhasil',
        ]);
    }

    #[Endpoint(title: 'Logout All', description: 'Revoke all tokens for the authenticated user. Flow: Auth (logout all devices).')]
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

    #[Endpoint(title: 'Forgot Password', description: 'Send password reset link to email. Always returns 200 (security: hide valid emails). Flow: Auth (password reset).')]
    #[BodyParameter(name: 'email', description: 'Registered email address', required: true, type: 'string')]
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'status' => 'success',
            'message' => 'Jika email terdaftar, link reset password akan dikirim.',
        ]);
    }

    #[Endpoint(title: 'Current User', description: 'Get authenticated user info with roles and permissions. Flow: Auth (after login).')]
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => $this->userPayload($request->user()),
        ]);
    }

    /**
     * Get active Sanctum token for current user.
     *
     * Returns the current Bearer token for the authenticated session.
     * Caches token for 5 minutes to reduce DB writes.
     *
     * @tags Auth
     */
    public function sanctumToken(Request $request): JsonResponse
    {
        $user = $request->user();
        $sessionId = session()->getId();

        $token = cache()->remember("sanctum_token:{$user->id}:{$sessionId}", 300, function () use ($user) {
            $user->tokens()->where('name', 'web-frontend')->delete();

            return $user->createToken('web-frontend')->plainTextToken;
        });

        return response()->json([
            'status' => 'success',
            'data' => ['token' => $token],
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
