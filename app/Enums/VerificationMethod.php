<?php

namespace App\Enums;

enum VerificationMethod: string
{
    case FACE = 'face';
    case FACE_VERIFIED = 'face_verified';
    // PIN/PIN_VERIFIED PERTAHAN untuk kompatibilitas data historis:
    // kolom attendances.verification_method bisa berisi 'pin_verified' dari sebelum
    // kebijakan face-only (PRD §1/§4). Tidak ada kode baru yang menghasilkan nilai ini.
    case PIN = 'pin';
    case PIN_VERIFIED = 'pin_verified';
    case MANUAL = 'manual';
    case OFFLINE = 'offline';

    public function label(): string
    {
        return match ($this) {
            self::FACE, self::FACE_VERIFIED => 'Verifikasi Wajah',
            self::PIN, self::PIN_VERIFIED => 'Verifikasi PIN',
            self::MANUAL => 'Manual oleh HRD',
            self::OFFLINE => 'Offline / Sinkronisasi',
        };
    }
}
