<?php

namespace App\Enums;

enum AttendanceException: string
{
    case LATE = 'late';
    case EARLY_LEAVE = 'early_leave';
    case MISSED_CLOCK_IN = 'missed_clock_in';
    case MISSED_CLOCK_OUT = 'missed_clock_out';

    public function label(): string
    {
        return match ($this) {
            self::LATE => 'Terlambat',
            self::EARLY_LEAVE => 'Pulang Cepat',
            self::MISSED_CLOCK_IN => 'Lupa Clock In',
            self::MISSED_CLOCK_OUT => 'Lupa Clock Out',
        };
    }
}
