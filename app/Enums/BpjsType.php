<?php

namespace App\Enums;

// Tipe backed enum string sudah sangat tepat
enum BpjsType: string
{
    case KESEHATAN = 'kesehatan';
    case JHT = 'jht';
    case JP = 'jp';
    case JKK = 'jkk';
    case JKM = 'jkm';

    // Fungsi label() lo udah perfect, nggak perlu diubah!
    public function label(): string
    {
        return match ($this) {
            self::KESEHATAN => 'BPJS Kesehatan',
            self::JHT => 'Jaminan Hari Tua',
            self::JP => 'Jaminan Pensiun',
            self::JKK => 'Jaminan Kecelakaan Kerja',
            self::JKM => 'Jaminan Kematian',
        };
    }

    /**
     * LOGIKA BISNIS: Mengecek apakah tipe BPJS ini memiliki Batas Atas Gaji (Ceiling)
     */
    public function hasCeiling(): bool
    {
        return match ($this) {
            self::KESEHATAN, self::JP => true,
            default => false,
        };
    }
}