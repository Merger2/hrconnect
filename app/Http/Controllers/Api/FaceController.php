<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\RegisterFaceRequest;
use App\Models\FaceDescriptor;
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

    #[Endpoint(title: 'Register Face', description: 'Enroll face embedding (128D) or geometry descriptor (129D) for biometric verification.')]
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

        $vectorString = '['.implode(',', $data['embedding']).']';

        $metadata = [
            'source' => 'web',
            'descriptor_type' => 'faceRecognitionNet',
            'descriptor_version' => 1,
        ];

        if (isset($data['captures'])) {
            $metadata['captures_count'] = count($data['captures']);
        }

        FaceDescriptor::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'embedding' => $vectorString,
                'is_active' => true,
                'metadata' => $metadata,
            ]
        );

        // TODO: Legacy face_embedding support removed. Face descriptors are now handled solely in FaceDescriptor model.
        // Re-enable when necessary, but for now, rely on FaceDescriptor system.
        // $employee->forceFill(['face_embedding' => $vectorString])->save();

        $response = [
            'status' => 'success',
            'message' => 'Data wajah berhasil didaftarkan',
            'data' => [
                'employee_id' => $employee->id,
                'face_registered_at' => now()->toIso8601String(),
            ],
        ];

        if (isset($metadata['descriptor_type'])) {
            $response['data']['descriptor_type'] = 'geometry';
        }

        if (isset($data['captures'])) {
            $response['data']['capture_count'] = count($data['captures']);
        }

        return response()->json($response);
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

        // Check if face is enrolled first
        if (! $this->faceService->hasFaceEnrolled($employee)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Face ID belum didaftarkan. Silakan daftarkan wajah terlebih dahulu.',
            ], 422);
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
