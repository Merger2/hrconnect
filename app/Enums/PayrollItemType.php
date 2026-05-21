<?php

namespace App\Enums;

enum PayrollItemType: string
{
    case ALLOWANCE = 'allowance';
    case DEDUCTION = 'deduction';

    public function label(): string
    {
        return match ($this) {
            self::ALLOWANCE => 'Tunjangan (Penambah)',
            self::DEDUCTION => 'Potongan (Pengurang)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ALLOWANCE => 'success',
            self::DEDUCTION => 'danger',
        };
    }

    public function getMultiplier(): int
    {
        return match ($this) {
            self::ALLOWANCE => 1,
            self::DEDUCTION => -1,
        };
    }
}
