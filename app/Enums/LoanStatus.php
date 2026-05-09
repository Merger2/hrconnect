<?php

namespace App\Enums;

enum LoanStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';
    case ACTIVE = 'active';
    case PAID_OFF = 'paid_off';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Persetujuan',
            self::APPROVED => 'Disetujui (Menunggu Pencairan)',
            self::REJECTED => 'Ditolak',
            self::CANCELLED => 'Dibatalkan',
            self::ACTIVE => 'Sedang Berjalan (Mulai Dipotong)',
            self::PAID_OFF => 'Lunas',
        };
    }

    public function isDeductedFromPayroll(): bool
    {
        return match ($this) {
            self::ACTIVE => true,
            default => false,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::APPROVED => 'blue',
            self::ACTIVE => 'green',
            self::PAID_OFF => 'slate',
            self::REJECTED => 'red',
            self::CANCELLED => 'gray',
        };
    }
}
