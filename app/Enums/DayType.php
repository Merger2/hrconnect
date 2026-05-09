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
            self::FULL_DAY => 'Satu Hari Penuh',
            self::MORNING => 'Setengah Hari (Pagi)',
            self::AFTERNOON => 'Setengah Hari (Siang)',
        };
    }

    public function getQuotaDeduction(): float
    {
        return match ($this) {
            self::FULL_DAY => 1.0,
            self::MORNING, self::AFTERNOON => 0.5,
        };
    }
}
