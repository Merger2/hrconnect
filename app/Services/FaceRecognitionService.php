<?php
namespace App\Services;

use App\Exceptions\FaceNotRecognizedException;
use App\Models\Employee;
use Exception;
use Illuminate\Support\Facades\DB;

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
            throw new Exception('Data biometrik wajah Anda belum terdaftar. Silakan hubungi HRD.', 400);
        }
        $vectorString = '[' . implode(',', $incomingVector) . ']';

        $result = DB::table('employees')
            ->select(DB::raw("face_embedding <=> '$vectorString'::vector AS distance"))
            ->where('id', $employee->id)
            ->first();
        
        if ($result->distance > self::MAX_DISTANCE) {
             //Hitung persentase kemiripan untuk ditampilkan di log error
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
