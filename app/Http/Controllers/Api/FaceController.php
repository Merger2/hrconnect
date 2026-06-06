<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterFaceRequest;
use App\Services\FaceRecognitionService;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;

#[Group('Face Recognition')]
class FaceController extends Controller
{
    public function __construct(
        protected FaceRecognitionService $faceService,
    ) {}

    #[Endpoint(title: 'Register Face', description: 'Enroll face embedding (128D FaceNet vector) for biometric verification.')]
    #[BodyParameter(name: 'embedding', description: '128-dimension face embedding array from face-api.js', required: true, type: 'array')]
    public function register(RegisterFaceRequest $request): JsonResponse
    {
        $data = $request->validated();

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

    #[Endpoint(title: 'Verify Face', description: 'Test face verification against enrolled embedding without recording attendance.')]
    #[BodyParameter(name: 'embedding', description: '128-dimension face embedding array to verify', required: true, type: 'array')]
    public function verify(RegisterFaceRequest $request): JsonResponse
    {
        $data = $request->validated();

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
