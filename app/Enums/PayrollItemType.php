<?php

namespace App\Enums;

enum PayrollItemType: string
{
    case ALLOWANCE = 'allowance';
    case OVERTIME = 'overtime';
    case DEDUCTION = 'deduction';
    case PENALTY = 'penalty';
    case BPJS = 'bpjs';

    public function label(): string
    {
        return match ($this) {
            self::ALLOWANCE => 'Tunjangan (Penambah)',
            self::OVERTIME => 'Lembur (Penambah)',
            self::DEDUCTION => 'Potongan (Pengurang)',
            self::PENALTY => 'Denda/Potongan Kehadiran',
            self::BPJS => 'BPJS (Kontribusi)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ALLOWANCE => 'success',
            self::OVERTIME => 'info',
            self::DEDUCTION => 'danger',
            self::PENALTY => 'warning',
            self::BPJS => 'secondary',
        };
    }

    public function getMultiplier(): int
    {
        return match ($this) {
            self::ALLOWANCE => 1,
            self::OVERTIME => 1,
            self::DEDUCTION => -1,
            self::PENALTY => -1,
            self::BPJS => -1,
        };
    }
}
