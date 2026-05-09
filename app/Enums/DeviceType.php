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
            self::DESKTOP => 'Komputer / Laptop',
            self::MOBILE => 'Smartphone',
            self::TABLET => 'Tablet / iPad',
        };
    }

    public function isAllowedForBiometric(): bool
    {
        return match ($this) {
            self::MOBILE, self::TABLET => true,
            self::DESKTOP => false,
        };
    }
}
