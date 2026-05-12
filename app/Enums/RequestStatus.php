<?php

namespace App\Enums;

enum RequestStatus: string
{
    case PENDING = 'pending';
    case APPROVED_L1 = 'approved_l1';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Persetujuan',
            self::APPROVED_L1 => 'Disetujui Atasan (Menunggu HRD)',
            self::APPROVED => 'Disetujui Final',
            self::REJECTED => 'Ditolak',
            self::CANCELLED => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED_L1 => 'info',
            self::APPROVED => 'success',
            self::REJECTED, self::CANCELLED => 'danger',
        };
    }
}
