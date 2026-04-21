<?php

namespace App\Enums;

enum SalaryType: string
{
    case MONTHLY = 'monthly';
    case WEEKLY = 'weekly';
    case DAILY = 'daily';

    public function label(): string
    {
        return match($this) {
            self::MONTHLY => 'Bulanan',
            self::WEEKLY => 'Mingguan',
            self::DAILY => 'Harian',
        };
    }
}
