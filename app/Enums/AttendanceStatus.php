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
    case MISSED_CLOCK_IN = 'missed_clock_in';
    case MISSED_CLOCK_OUT = 'missed_clock_out';

    public function label(): string
    {
        return match ($this) {
            self::ON_TIME => 'Tepat Waktu',
            self::LATE => 'Terlambat',
            self::EARLY => 'Pulang Cepat',
            self::ABSENT => 'Mangkir (Alfa)',
            self::PERMISSION => 'Izin',
            self::HOLIDAY => 'Hari Libur',
            self::MISSED_CLOCK_IN => 'Lupa Absen Masuk',
            self::MISSED_CLOCK_OUT => 'Lupa Absen Pulang',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ON_TIME, self::HOLIDAY, self::PERMISSION => 'success',
            self::LATE, self::EARLY => 'warning',
            self::ABSENT, self::MISSED_CLOCK_IN, self::MISSED_CLOCK_OUT => 'danger',
        };
    }

    public function isPenalty(): bool
    {
        return match ($this) {
            self::ABSENT, self::MISSED_CLOCK_IN, self::MISSED_CLOCK_OUT => true,
            default => false,
        };
    }
}
