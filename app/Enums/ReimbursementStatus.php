<?php

namespace App\Enums;

enum ReimbursementStatus: string
{
    case PENDING = 'pending';
    case APPROVED_L1 = 'approved_l1';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PAID = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Sedang Diproses',
            self::APPROVED_L1 => 'Disetujui Atasan (Menunggu Finance)',
            self::APPROVED => 'Disetujui Final',
            self::REJECTED => 'Ditolak',
            self::PAID => 'Sudah Dibayarkan',
        };
    }

    public function isSettled(): bool
    {
        return match ($this) {
            self::PAID, self::REJECTED => true,
            default => false,
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::APPROVED_L1 => 'info',
            self::APPROVED => 'success',
            self::PAID => 'success',
            self::REJECTED => 'danger',
        };
    }
}
