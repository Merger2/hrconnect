<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case PAID = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft (Belum Fix)',
            self::PUBLISHED => 'Diterbitkan (Menunggu Transfer)',
            self::PAID => 'Telah Dibayar',
        };
    }
}
