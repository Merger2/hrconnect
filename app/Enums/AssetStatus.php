<?php

namespace App\Enums;

enum AssetStatus: string
{
    case AVAILABLE = 'available';
    case ASSIGNED = 'assigned';
    case DISPOSED = 'disposed';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Tersedia',
            self::ASSIGNED => 'Sedang Digunakan',
            self::DISPOSED => 'Dihapus / Rusak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'green',
            self::ASSIGNED => 'blue',
            self::DISPOSED => 'red',
        };
    }
}
