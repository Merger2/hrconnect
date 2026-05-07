<?php

namespace App\Enums;

enum ShiftScheduleType: string
{
    case REGULAR = 'regular';
    case ROTATING = 'rotating';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::REGULAR => 'Reguler',
            self::ROTATING => 'Rotasi',
            self::CUSTOM => 'Kustom',
        };
    }
}
