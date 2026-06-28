<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

#[Group('Auth')]
class EmailVerificationController extends Controller
{
    #[Endpoint(title: 'Verify Email', description: 'Verify user email via signed URL from email notification. Public endpoint called when user clicks verification link.')]
    public function verify(EmailVerificationRequest $request, string $id, string $hash): JsonResponse
    {
        $user = User::findOrFail($id);

        if (! hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Link verifikasi tidak valid.',
            ], 400);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Email sudah diverifikasi sebelumnya.',
            ]);
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Email berhasil diverifikasi.',
        ]);
    }

    #[Endpoint(title: 'Resend Verification Email', description: 'Resend the email verification notification to the authenticated user. Requires valid Bearer token.')]
    public function resend(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 'success',
                'message' => 'Email sudah diverifikasi.',
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'status' => 'success',
            'message' => 'Link verifikasi telah dikirim ulang ke email Anda.',
        ]);
    }
}
