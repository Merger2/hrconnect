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
            self::SINGLE => 'Belum Kawin (TK)',
            self::MARRIED => 'Kawin (K)',
            self::DIVORCED => 'Cerai Hidup',
            self::WIDOWED => 'Cerai Mati',
        };
    }
}
