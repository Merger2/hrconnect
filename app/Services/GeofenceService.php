<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\AntiFakeGPSException;
use App\Exceptions\BusinessRuleException;
use App\Exceptions\GeofenceViolationException;
use App\Models\Branch;

class GeofenceService
{
    // Konstanta: Jari-jari bumi dalam hitungan meter. Digunakan untuk perhitungan jarak geofence.
    private const EARTH_RADIUS__METERS = 6371000;

    public function validateLocation(Branch $branch, array $gpsData): array
    {
        if (isset($gpsData['is_mocked']) && $gpsData['is_mocked'] === true) {
            throw new AntiFakeGPSException('Peringatan: Aplikasi Fake GPS terdeteksi aktif di perangkat Anda! Pastikan untuk menonaktifkan aplikasi tersebut dan coba lagi.');
        }

        if (isset($gpsData['accuracy']) && $gpsData['accuracy'] > 100) {
            throw new AntiFakeGPSException('Peringatan: Akurasi GPS terlalu rendah ('.$gpsData['accuracy'].' meter). Pastikan Anda berada di area terbuka untuk hasil terbaik.');
        }

        // B3.2 fix: validasi koordinat sebelum hitung Haversine.
        // Tanpa guard ini, deg2rad(null) silently jadi 0 → false-positive
        // "user di branch coordinates" yang membatalkan validasi GPS.
        $this->assertValidCoordinates($gpsData);
        $this->assertValidBranchCoordinates($branch);

        $distance = $this->calculateHaversine(
            (float) $branch->latitude,
            (float) $branch->longitude,
            (float) $gpsData['latitude'],
            (float) $gpsData['longitude']
        );

        if ($branch->radius === null) {
            throw new BusinessRuleException(
                "Cabang '{$branch->name}' belum punya radius geofence. Hubungi HRD untuk konfigurasi."
            );
        }

        $isWithinRadius = $distance <= $branch->radius;
        if (! $isWithinRadius) {
            $formattedDistance = number_format($distance, 2);
            throw new GeofenceViolationException("Anda berada di luar jangkauan kantor. Jarak Anda: {$formattedDistance} meter.");
        }

        return [
            'valid' => true,
            'distance' => $distance,
        ];
    }

    /**
     * B3.2 fix: validasi koordinat dari client (PWA).
     * Cegah null/string/range invalid lolos ke deg2rad() yang silent error.
     */
    private function assertValidCoordinates(array $gpsData): void
    {
        if (! isset($gpsData['latitude'], $gpsData['longitude'])) {
            throw new BusinessRuleException('Koordinat GPS tidak dikirim. Pastikan izin lokasi diaktifkan.');
        }

        if (! is_numeric($gpsData['latitude']) || ! is_numeric($gpsData['longitude'])) {
            throw new BusinessRuleException('Format koordinat GPS tidak valid (harus angka).');
        }

        $lat = (float) $gpsData['latitude'];
        $lng = (float) $gpsData['longitude'];

        if (abs($lat) > 90 || abs($lng) > 180) {
            throw new BusinessRuleException("Koordinat GPS di luar range valid (lat: {$lat}, lng: {$lng}).");
        }

        if ($lat === 0.0 && $lng === 0.0) {
            // (0, 0) adalah Null Island di samudera Atlantik — hampir pasti GPS error
            throw new BusinessRuleException('Koordinat GPS (0, 0) terdeteksi. Pastikan GPS sudah lock signal.');
        }
    }

    /**
     * B3.2 fix: validasi koordinat branch dari database.
     * Cegah branch tanpa koordinat lolos validasi (data integrity check).
     */
    private function assertValidBranchCoordinates(Branch $branch): void
    {
        if ($branch->latitude === null || $branch->longitude === null) {
            throw new BusinessRuleException(
                "Cabang '{$branch->name}' belum punya koordinat GPS. Hubungi HRD untuk konfigurasi geofence."
            );
        }
    }

    private function calculateHaversine(float $lat1, float $lon1, float $lat2, float $lon2): float
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
