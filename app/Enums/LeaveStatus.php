<?php

namespace App\Enums;

enum LeaveStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Persetujuan',
            self::APPROVED => 'Cuti Disetujui',
            self::REJECTED => 'Cuti Ditolak',
            self::CANCELLED => 'Dibatalkan Karyawan',
        };
    }
}
