<?php

namespace App\Enums;

enum SalaryType: string
{
    case MONTHLY = 'monthly';
    case HOURLY = 'hourly';
    case DAILY = 'daily';

    public function label(): string
    {
        return match ($this) {
            self::MONTHLY => 'Bulanan',
            self::HOURLY => 'Per Jam',
            self::DAILY => 'Harian',
        };
    }
}
