<?php

namespace App\Enums;

enum ResignationReason: string
{
    case PERSONAL = 'personal';
    case BETTER_OFFER = 'better_offer';
    case RELOCATION = 'relocation';
    case HEALTH = 'health';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PERSONAL => 'Alasan Pribadi',
            self::BETTER_OFFER => 'Penawaran Lebih Baik',
            self::RELOCATION => 'Relokasi',
            self::HEALTH => 'Kesehatan',
            self::OTHER => 'Lainnya',
        };
    }
}
