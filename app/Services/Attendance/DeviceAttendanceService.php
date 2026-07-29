<?php

declare(strict_types=1);

namespace App\Services\Attendance;

class DeviceAttendanceService
{
    public function uploadPhoto(int|string $userId, $photo, ?float $latitude = null, ?float $longitude = null): array
    {
        return [
            'attendance' => (object) ['id' => 0],
            'slot' => 'in',
        ];
    }
}
