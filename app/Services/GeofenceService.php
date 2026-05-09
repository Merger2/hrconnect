<?php
namespace App\Services;

use App\Exceptions\AntiFakeGPSException;
use App\Exceptions\GeofenceViolationException;
use App\Models\Branch;

class GeofenceService
{
    // Konstanta: Jari-jari bumi dalam hitungan meter. Digunakan untuk perhitungan jarak geofence.
    private const EARTH_RADIUS__METERS = 6371000;
    public function validateLocation(Branch $branch, array $gpsData): array
    {
        if(isset($gpsData['is_mocked']) && $gpsData['is_mocked'] === true) {
            throw new AntiFakeGPSException('Peringatan: Aplikasi Fake GPS terdeteksi aktif di perangkat Anda! Pastikan untuk menonaktifkan aplikasi tersebut dan coba lagi.');
        }

        if(isset($gpsData['accuracy']) && $gpsData['accuracy'] > 100) {
            throw new AntiFakeGPSException('Peringatan: Akurasi GPS terlalu rendah (' . $gpsData['accuracy'] . ' meter). Pastikan Anda berada di area terbuka untuk hasil terbaik.');
        }
        $distance = $this->calculateHaversine(
            $branch->latitude,
            $branch->longitude,
            $gpsData['latitude'],
            $gpsData['longitude']
        );

        $isWithinRadius = $distance <= $branch->radius;
        if(!$isWithinRadius) {
            $formattedDistance = number_format($distance, 2);
            throw new GeofenceViolationException("Anda berada di luar jangkauan kantor. Jarak Anda: {$formattedDistance} meter.");
        }

        return [
            'valid' => true,
            'distance' => $distance,
        ];
    }

    private function calculateHaversine($lat1, $lon1, $lat2, $lon2): float
    {
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        $dLat = $lat2 - $lat1;
        $dLon = $lon2 - $lon1;
        
        // pow() = pangkat (power), sin() = sinus, cos() = kosinus
        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($dLon / 2) * sin($dLon / 2);
        
        // asin() = arcsine, sqrt() = akar kuadrat (square root)     
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS__METERS * $c;
    }
}