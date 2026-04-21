<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case ON_TIME = 'on_time';
    case LATE = 'late';
    case EARLY = 'early';
    case ABSENT = 'absent';
    case PERMISSION = 'permission';
    case HOLIDAY = 'holiday';

    public function label(): string
    {
        return match ($this) {
            self::ON_TIME => 'Hadir Tepat Waktu',
            self::LATE => 'Terlambat Masuk',
            self::EARLY => 'Pulang Cepat',
            self::ABSENT => 'Mangkir / Alpa',
            self::PERMISSION => 'Izin / Sakit',
            self::HOLIDAY => 'Libur Nasional',
        };
    }
}
