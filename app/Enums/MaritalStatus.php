<?php

namespace App\Enums;

enum MaritalStatus: string
{
    case SINGLE = 'single';
    case MARRIED = 'married';
    case DIVORCED = 'divorced';
    case WIDOWED = 'widowed';

    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Lajang / Belum Kawin',
            self::MARRIED => 'Menikah',
            self::DIVORCED => 'Cerai Hidup',
            self::WIDOWED => 'Cerai Mati',
        };
    }
}
