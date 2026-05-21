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
            self::C => 'Kategori C (K/2, K/3)',
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
        $dependents = min($dependents, 3);

        if (in_array($status, [MaritalStatus::SINGLE, MaritalStatus::DIVORCED, MaritalStatus::WIDOWED])) {
            return match ($dependents) {
                0, 1 => self::A,
                2, 3 => self::B,
            };
        }

        return match ($dependents) {
            0, 1 => self::B,
            2, 3 => self::C,
        };
    }
}
