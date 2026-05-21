<?php

namespace App\Enums;

enum VerificationMethod: string
{
    case FACE_VERIFIED = 'face_verified';
    case PIN_VERIFIED = 'pin_verified';
    case MANUAL = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::FACE_VERIFIED => 'Verifikasi Wajah',
            self::PIN_VERIFIED => 'Verifikasi PIN',
            self::MANUAL => 'Manual oleh HRD',
        };
    }
}
