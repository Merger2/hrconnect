<?php

namespace App\Enums;

enum LoanInstallmentStatus: string
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case OVERDUE = 'overdue';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Belum Dibayar',
            self::PAID => 'Lunas',
            self::OVERDUE => 'Jatuh Tempo',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PAID => 'success',
            self::OVERDUE => 'danger',
        };
    }
}
