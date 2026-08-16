<?php

namespace App\Enums;

enum TerCategory: string
{
    case A = 'A';
    case B = 'B';
    case C = 'C';

    public function label(): string
    {
        return match ($this) {
            self::A => 'Kategori A (TK/0, TK/1)',
            self::B => 'Kategori B (TK/2, TK/3, K/0, K/1)',
            self::C => 'Kategori C (K/2, K/3 atau lebih)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::A => 'info',
            self::B => 'warning',
            self::C => 'danger',
        };
    }

    public static function resolveFromStatus(MaritalStatus $status, int $dependents): self
    {
        $dependents = max(0, min($dependents, 3));

        if (in_array($status, [MaritalStatus::SINGLE, MaritalStatus::DIVORCED, MaritalStatus::WIDOWED])) {
            return match ($dependents) {
                0, 1 => self::A,
                2, 3 => self::B,
            };
        }

        return match ($dependents) {
            0 => self::A,
            1 => self::B,
            2, 3 => self::C,
        };
    }

    public static function ptkpLabel(MaritalStatus $status, int $dependents): string
    {
        $prefix = match ($status) {
            MaritalStatus::MARRIED => 'K',
            default => 'TK',
        };

        $dependentsLabel = $dependents > 3 ? '3+' : (string) max(0, $dependents);

        return "{$prefix}/{$dependentsLabel}";
    }

    public function golonganPtkpKode(): string
    {
        return match ($this) {
            self::A => 'TK/0',
            self::B => 'K/0',
            self::C => 'K/3',
        };
    }
}
