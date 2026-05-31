<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Exceptions\FaceNotRecognizedException;
use App\Exceptions\FaceNotRegisteredException;
use App\Models\Employee;

class FaceRecognitionService
{
    /**
     * Dalam matematika vektor, kemiripan 85% sama dengan Jarak (Distance) maksimal 0.15.
     * Jika jarak antar vektor lebih dari 0.15, berarti itu wajah orang lain!
     */
    private const MAX_DISTANCE = 0.15;

    /**
     * Konstanta dimensi face embedding.
     * Sumber: face-api.js FaceNet model = 128 dimensi (PRD §13.3, AGENTS.md).
     */
    private const EMBEDDING_DIMENSIONS = 128;

    /**
     * LOGIKA BISNIS: Memvalidasi apakah wajah yang absen sama dengan pemilik akun.
     */
    public function verifyFace(Employee $employee, array $incomingVector): array
    {
        // Pakai getRawOriginal supaya tidak trigger pgvector cast (sama dengan
        // pattern di AttendanceService::resolveVerification — Sesi 4 B12 fix).
        $hasEmbedding = ! empty($employee->getRawOriginal('face_embedding'))
            || ! empty($employee->getAttributes()['face_embedding'] ?? null);

        if (! $hasEmbedding) {
            throw new FaceNotRegisteredException('Data biometrik wajah Anda belum terdaftar. Silakan hubungi HRD.');
        }

        // B3.12 fix: validasi dimensi vector. FaceNet (face-api.js) selalu 128D.
        // Vector dengan dimensi berbeda (misal 192/512 dari model lain) akan
        // bikin pgvector error / hasil distance ngawur.
        if (count($incomingVector) !== self::EMBEDDING_DIMENSIONS) {
            $expected = self::EMBEDDING_DIMENSIONS;
            $got = count($incomingVector);
            throw new BusinessRuleException(
                "Format embedding wajah tidak valid: harus {$expected}D (FaceNet), diterima {$got}D."
            );
        }

        foreach ($incomingVector as $value) {
            if (! is_numeric($value)) {
                throw new BusinessRuleException('Format data biometrik wajah tidak valid.');
            }
        }

        $vectorString = '['.implode(',', $incomingVector).']';

        $result = Employee::selectRaw('face_embedding <=> ?::vector AS distance', [$vectorString])
            ->where('id', $employee->id)
            ->firstOrFail();

        if ($result->distance > self::MAX_DISTANCE) {
            // Hitung persentase kemiripan untuk ditampilkan di log error
            $similarityPercentage = (1 - $result->distance) * 100;
            $formattedSim = number_format($similarityPercentage, 2);
            throw new FaceNotRecognizedException("Wajah tidak dikenali! Kemiripan hanya {$formattedSim}%. Pastikan pencahayaan terang dan tidak memakai masker.");
        }

        return [
            'valid' => true,
            'similarity_percentage' => (1 - $result->distance) * 100,
        ];

    }
}
