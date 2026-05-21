<?php

namespace App\Enums;

enum DayType: string
{
    case FULL_DAY = 'full_day';
    case MORNING = 'morning';
    case AFTERNOON = 'afternoon';

    public function label(): string
    {
        return match ($this) {
            self::FULL_DAY => 'Full Day (1.0 Hari)',
            self::MORNING => 'Pagi (0.5 Hari)',
            self::AFTERNOON => 'Siang (0.5 Hari)',
        };
    }

    public function weight(): float
    {
        return match ($this) {
            self::FULL_DAY => 1.0,
            self::MORNING, self::AFTERNOON => 0.5,
        };
    }
}
