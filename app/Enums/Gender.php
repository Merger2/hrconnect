<?php

namespace App\Enums;

enum Gender: string
{
    case MALE = 'L';
    case FEMALE = 'P';

    public function label(): string
    {
        return match ($this) {
            self::MALE => 'Laki-Laki',
            self::FEMALE => 'Perempuan',
        };
    }
}