<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\FaceRecognitionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * FaceController — face enrollment + verification untuk PWA.
 *
 * Endpoint:
 * - POST /face/register : enroll embedding 128D ke employee
 * - POST /face/verify   : test verifikasi tanpa create attendance
 *
 * Embedding harus 128D (FaceNet via face-api.js client-side). Dimension
 * validation di FaceRecognitionService (B3.12 fix).
 */
class FaceController extends Controller
{
    public function __construct(
        protected FaceRecognitionService $faceService,
    ) {}

    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'embedding' => ['required', 'array', 'size:128'],
            'embedding.*' => ['numeric', 'between:-1.5,1.5'],
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        // Format vector untuk pgvector ([0.1,0.2,...]).
        $vectorString = '['.implode(',', $data['embedding']).']';
        $employee->forceFill(['face_embedding' => $vectorString])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Data wajah berhasil didaftarkan',
            'data' => [
                'employee_id' => $employee->id,
                'face_registered_at' => now()->toIso8601String(),
            ],
        ]);
    }

    public function verify(Request $request): JsonResponse
    {
        $data = $request->validate([
            'embedding' => ['required', 'array', 'size:128'],
            'embedding.*' => ['numeric', 'between:-1.5,1.5'],
        ]);

        $employee = $request->user()->employee;

        if (! $employee) {
            return response()->json([
                'status' => 'error',
                'message' => 'Akun Anda belum terhubung dengan data karyawan.',
            ], 404);
        }

        $result = $this->faceService->verifyFace($employee, $data['embedding']);

        return response()->json([
            'status' => 'success',
            'message' => 'Wajah dikenali',
            'data' => [
                'valid' => $result['valid'],
                'similarity_percentage' => round($result['similarity_percentage'], 2),
            ],
        ]);
    }
}
