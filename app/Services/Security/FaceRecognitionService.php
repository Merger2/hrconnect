<?php

declare(strict_types=1);

namespace App\Services\Security;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRecognizedException;
use App\Exceptions\FaceNotRegisteredException;
use App\Models\Employee;
use App\Models\FaceDescriptor;
use Pgvector\Laravel\Vector;

class FaceRecognitionService
{
    public const EMBEDDING_DIMENSION = 128;

    public const SIMILARITY_THRESHOLD = 85.0;

    /**
     * Similarity threshold (%) derived from config('hrconnect.face_distance_threshold').
     * Config menyimpan batas jarak cosine (0.0–1.0); 0.15 ≈ 85% similarity.
     * Default 0.15 menjaga perilaku lama (85.0).
     */
    public static function similarityThreshold(): float
    {
        $distance = (float) config('hrconnect.face_distance_threshold', 0.15);

        return (1 - $distance) * 100;
    }

    /**
     * Verify a face embedding against registered employees.
     *
     * @param  array<float>  $embedding
     * @return array{employee_id: int, similarity_percentage: float}
     *
     * @throws FaceNotRecognizedException|BusinessRuleException|FaceNotRegisteredException
     */
    public function verifyFace(Employee $employee, array $embedding): array
    {
        $embedding = $this->normalizeEmbedding($embedding);
        $this->validateEmbedding($embedding);

        $descriptor = $employee->faceDescriptors()->first();

        if (! $descriptor) {
            throw new FaceNotRegisteredException('Wajah karyawan belum terdaftar.');
        }

        $vector = new Vector($embedding);

        $match = FaceDescriptor::query()
            ->where('employee_id', $employee->id)
            ->orderByRaw('embedding <=> ?', [$vector])
            ->first();

        if (! $match) {
            throw new FaceNotRecognizedException('Wajah tidak dikenali.');
        }

        $similarity = 1 - $this->cosineDistance($match->embedding, $vector);
        $similarityPercentage = $similarity * 100;

        if ($similarityPercentage < self::similarityThreshold()) {
            throw new FaceNotRecognizedException(
                "Wajah tidak dikenali (similarity: {$similarityPercentage}%)"
            );
        }

        return [
            'employee_id' => $employee->id,
            'similarity_percentage' => round($similarityPercentage, 1),
        ];
    }

    public function saveFaceDescriptor(Employee $employee, array $embedding): void
    {
        $embedding = $this->normalizeEmbedding($embedding);
        $this->validateEmbedding($embedding);

        FaceDescriptor::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'embedding' => new Vector($embedding),
                'is_active' => true,
            ]
        );
    }

    public function registerFace(Employee $employee, array $embedding): void
    {
        $this->saveFaceDescriptor($employee, $embedding);
    }

    public function hasFaceEnrolled(Employee $employee): bool
    {
        return $employee->faceDescriptors()->exists();
    }

    public function getEmbeddingDimension(): int
    {
        return self::EMBEDDING_DIMENSION;
    }

    /**
     * Normalize embedding: strip 129→128 jika elemen pertama adalah marker (2).
     *
     * @param  array<float>  $embedding
     * @return array<float>
     */
    private function normalizeEmbedding(array $embedding): array
    {
        // Geometry descriptor dari face-api.js punya marker [2] di index[0] (129 total).
        if (count($embedding) === 129 && ($embedding[0] === 2 || $embedding[0] === 3)) {
            return array_slice($embedding, 1);
        }

        return $embedding;
    }

    /**
     * @param  array<float>  $embedding
     *
     * @throws BusinessRuleException
     */
    private function validateEmbedding(array $embedding): void
    {
        if (count($embedding) !== self::EMBEDDING_DIMENSION) {
            throw new BusinessRuleException(
                'Vector embedding harus '.self::EMBEDDING_DIMENSION.'D, diterima '.count($embedding).'D.'
            );
        }

        foreach ($embedding as $value) {
            if (! is_numeric($value)) {
                throw new BusinessRuleException(
                    'Vector embedding tidak valid: semua nilai harus numerik.'
                );
            }
        }
    }

    /**
     * Calculate cosine distance between two vectors.
     */
    private function cosineDistance(Vector $a, Vector $b): float
    {
        $va = $a->toArray();
        $vb = $b->toArray();

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($i = 0; $i < count($va); $i++) {
            $dot += $va[$i] * $vb[$i];
            $normA += $va[$i] * $va[$i];
            $normB += $vb[$i] * $vb[$i];
        }

        if ($normA == 0.0 || $normB == 0.0) {
            return 1.0;
        }

        return 1.0 - ($dot / (sqrt($normA) * sqrt($normB)));
    }
}
