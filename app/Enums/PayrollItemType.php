<?php

namespace App\Enums;

enum PayrollItemType: string
{
    case ALLOWANCE = 'allowance';
    case DEDUCTION = 'deduction';

    public function label(): string
    {
        return match ($this) {
            self::ALLOWANCE => 'Tunjangan',
            self::DEDUCTION => 'Potongan',
        };
    }
}
