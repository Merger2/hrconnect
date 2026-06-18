<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRecognizedException;
use App\Exceptions\FaceNotRegisteredException;
use App\Models\CompanySetting;
use App\Models\Employee;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Pgvector\Laravel\Distance;
use Pgvector\Laravel\Vector;

class FaceRecognitionService
{
    const EMBEDDING_DIMENSION = 128;

    private function getFaceDistanceThreshold(): float
    {
        try {
            $value = CompanySetting::get('face_distance_threshold');

            return $value !== null ? (float) $value : (float) config('hrconnect.face_distance_threshold', 0.15);
        } catch (QueryException $e) {
            Log::warning('CompanySetting table not available, using config default', ['error' => $e->getMessage()]);

            return (float) config('hrconnect.face_distance_threshold', 0.15);
        }
    }

    public function getEmbeddingDimension(): int
    {
        return self::EMBEDDING_DIMENSION;
    }

    public function verifyFace(Employee $employee, array $embedding): array
    {
        $maxDistance = (float) ($this->getFaceDistanceThreshold());

        if (count($embedding) !== self::EMBEDDING_DIMENSION) {
            throw new BusinessRuleException('Vector embedding harus 128D.');
        }

        $hasNonNumeric = collect($embedding)->contains(fn ($v) => ! is_numeric($v));
        if ($hasNonNumeric) {
            throw new BusinessRuleException('Vector embedding tidak valid: semua nilai harus numerik.');
        }

        $hasFace = ! empty($employee->getRawOriginal('face_embedding'))
            || ! empty($employee->getAttributes()['face_embedding'] ?? null);

        if (! $hasFace) {
            throw new FaceNotRegisteredException(
                'Wajah karyawan ini belum terdaftar. Silakan hubungi HRD untuk registrasi wajah.'
            );
        }

        $employee->load('user');
        $vector = new Vector($embedding);

        try {
            $result = Employee::where('id', $employee->id)
                ->nearestNeighbors('face_embedding', $vector, Distance::Cosine)
                ->first();
        } catch (QueryException $e) {
            Log::error('Face recognition vector query failed', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            throw new BusinessRuleException('Layanan face recognition sedang tidak tersedia. Gunakan PIN sebagai fallback.');
        }

        if (! $result) {
            throw new FaceNotRecognizedException('Wajah tidak dikenali. Silakan coba lagi dengan pencahayaan yang cukup.');
        }

        $distance = $result->neighbor_distance;

        if ($distance > $maxDistance) {
            $similarityPercentage = (1 - $distance) * 100;
            $formattedSim = number_format($similarityPercentage, 2);
            throw new FaceNotRecognizedException("Wajah tidak dikenali! Kemiripan hanya {$formattedSim}%. Pastikan pencahayaan terang dan tidak memakai masker.");
        }

        return [
            'valid' => true,
            'similarity_percentage' => (1 - $distance) * 100,
        ];
    }
}
