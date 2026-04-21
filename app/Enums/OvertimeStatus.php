<?php

namespace App\Enums;

enum OvertimeStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case PROCESSED = 'processed';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Menunggu Persetujuan',
            self::APPROVED => 'Lembur Disetujui',
            self::REJECTED => 'Lembur Ditolak',
            self::PROCESSED => 'Sudah Masuk Gaji',
        };
    }    
}
