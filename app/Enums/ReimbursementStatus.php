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
            self::PENDING => 'Sedang Diproses',
            self::APPROVED => 'Disetujui',
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
            self::PENDING => 'yellow',
            self::APPROVED => 'blue',
            self::PAID => 'green',
            self::REJECTED => 'red',
        };
    }
}
