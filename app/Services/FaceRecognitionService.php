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
     * LOGIKA BISNIS: Memvalidasi apakah wajah yang absen sama dengan pemilik akun.
     */
    public function verifyFace(Employee $employee, array $incomingVector): array
    {
        if (empty($employee->face_embedding)) {
            throw new FaceNotRegisteredException('Data biometrik wajah Anda belum terdaftar. Silakan hubungi HRD.');
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
