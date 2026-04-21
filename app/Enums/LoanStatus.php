<?php

namespace App\Enums;

enum LoanStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case ACTIVE = 'active';
    case PAID_OFF = 'paid_off';

    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Menunggu Persetujuan',
            self::APPROVED => 'Pinjaman Disetujui',
            self::REJECTED => 'Pinjaman Ditolak',
            self::ACTIVE => 'Sedang Dalam Cicilan',
            self::PAID_OFF => 'Pinjaman Lunas',
        };
    }
}