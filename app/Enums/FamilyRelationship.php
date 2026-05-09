<?php

namespace App\Enums;

enum FamilyRelationship: string
{
    case SPOUSE = 'spouse';
    case PARENT = 'parent';
    case CHILD = 'child';
    case SIBLING = 'sibling';
    case FRIEND = 'friend';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::SPOUSE => 'Suami / Istri',
            self::PARENT => 'Orang Tua',
            self::CHILD => 'Anak',
            self::SIBLING => 'Saudara Kandung',
            self::FRIEND => 'Teman / Relasi',
            self::OTHER => 'Lainnya',
        };
    }
}
