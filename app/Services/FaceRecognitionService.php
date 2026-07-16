<?php

namespace App\Services;

use App\Models\FaceDescriptor;
use App\Models\Employee;

class FaceRecognitionService
{
    public function getEmbeddingDimension(): int
    {
        return 68;
    }

    public function verifyFace(Employee $employee, array $embedding): array
    {
        $descriptors = FaceDescriptor::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->get();

        if ($descriptors->isEmpty()) {
            return [
                'employee_id' => $employee->id,
                'valid' => false,
                'similarity_percentage' => 0,
                'is_match' => false,
                'match_score' => 0,
                'threshold' => 0.4,
                'matching_descriptors' => collect(),
            ];
        }

        $scores = [];
        foreach ($descriptors as $descriptor) {
            $score = $this->calculateSimilarity($embedding, $descriptor->embedding);
            $scores[] = $score;
        }

        // Majority voting: at least 3 of 5 descriptors must match
        $threshold = 0.4;
        $matchCount = collect($scores)->filter(fn($s) => $s >= $threshold)->count();
        $isMatch = $matchCount >= 3;

        $avgScore = collect($scores)->avg();

        return [
            'employee_id' => $employee->id,
            'valid' => $isMatch,
            'is_match' => $isMatch,
            'similarity_percentage' => round($avgScore * 100, 2),
            'match_score' => $avgScore,
            'match_count' => $matchCount,
            'threshold' => $threshold,
            'matching_descriptors' => $descriptors->filter(
                fn($d) => $this->calculateSimilarity($embedding, $d->embedding) >= $threshold
            ),
        ];
    }

    public function hasFaceEnrolled(Employee $employee): bool
    {
        return FaceDescriptor::where('employee_id', $employee->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * Calculate cosine similarity between two embeddings
     */
    public function calculateSimilarity(array $embedding1, array $embedding2): float
    {
        $dotProduct = 0;
        $norm1 = 0;
        $norm2 = 0;

        foreach ($embedding1 as $i => $value1) {
            $norm1 += $value1 * $value1;
            $norm2 += $embedding2[$i] * $embedding2[$i];
            $dotProduct += $value1 * $embedding2[$i];
        }

        if ($norm1 === 0 || $norm2 === 0) {
            return 0;
        }

        return $dotProduct / (sqrt($norm1) * sqrt($norm2));
    }

    /**
     * Save face descriptor for an employee.
     *
     * @param Employee $employee The employee to save the descriptor for.
     * @param array $descriptor A 128D or 129D array of face descriptor values.
     */
    public function saveFaceDescriptor(Employee $employee, array $descriptor): void
    {
        FaceDescriptor::updateOrCreate(
            ['employee_id' => $employee->id],
            [
                'embedding' => $descriptor,
                'is_active' => true,
                'metadata' => ['source' => 'web'],
            ]
        );
        // employees.face_embedding was dropped — see migration 2026_07_16_000100
        // Do not write to non-existent column
    }
}