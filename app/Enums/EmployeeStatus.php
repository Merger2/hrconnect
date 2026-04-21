<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case ACTIVE = 'active';
    case PROBATION = 'probation';
    case RESIGN = 'resign';
    case TERMINATED = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Karyawan Aktif',
            self::PROBATION => 'Masa Percobaan (Probation)',
            self::RESIGN => 'Mengundurkan Diri',
            self::TERMINATED => 'Diberhentikan (PHK)',
        };
    }
}
