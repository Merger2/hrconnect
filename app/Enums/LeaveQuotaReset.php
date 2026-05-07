<?php

namespace App\Enums;

enum LeaveQuotaReset: string
{
    case YEARLY = 'yearly';
    case MONTHLY = 'monthly';
    case ONE_TIME = 'one_time';

    public function label(): string
    {
        return match ($this) {
            self::YEARLY => 'Tahunan',
            self::MONTHLY => 'Bulanan',
            self::ONE_TIME => 'Sekali Pakai',
        };
    }
}
