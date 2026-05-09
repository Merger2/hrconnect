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
            self::DRAFT => 'Draft (Bisa Direvisi)',
            self::PUBLISHED => 'Diterbitkan (Terkunci Permanen)',
            self::PAID => 'Dibayar',
        };
    }
}
