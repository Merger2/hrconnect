<?php

namespace App\Enums;

enum ReimbursementStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PAID = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Persetujuan',
            self::APPROVED => 'Klaim Disetujui',
            self::REJECTED => 'Klaim Ditolak',
            self::PAID => 'Dana Telah Ditransfer',
        };
    }
}
