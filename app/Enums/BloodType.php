<?php

namespace App\Enums;

enum BloodType: string
{
    case A_POSITIVE = 'A+';
    case A_NEGATIVE = 'A-';
    case B_POSITIVE = 'B+';
    case B_NEGATIVE = 'B-';
    case O_POSITIVE = 'O+';
    case O_NEGATIVE = 'O-';
    case AB_POSITIVE = 'AB+';
    case AB_NEGATIVE = 'AB-';

    public function label(): string
    {
        return match ($this) {
            self::A_POSITIVE => 'A Positif (A+)',
            self::A_NEGATIVE => 'A Negatif (A-)',
            self::B_POSITIVE => 'B Positif (B+)',
            self::B_NEGATIVE => 'B Negatif (B-)',
            self::O_POSITIVE => 'O Positif (O+)',
            self::O_NEGATIVE => 'O Negatif (O-)',
            self::AB_POSITIVE => 'AB Positif (AB+)',
            self::AB_NEGATIVE => 'AB Negatif (AB-)',
        };
    }
}
