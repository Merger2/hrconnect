<?php

namespace App\Enums;

enum DeviceType: string
{
    case DESKTOP = 'desktop';
    case MOBILE = 'mobile';
    case TABLET = 'tablet';

    public function label(): string
    {
        return match ($this) {
            self::DESKTOP => 'Desktop/Laptop',
            self::MOBILE => 'Handphone',
            self::TABLET => 'Tablet',
        };
    }
}
