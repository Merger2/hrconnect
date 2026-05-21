<?php

namespace App\Enums;

enum EmployeeStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case RESIGNED = 'resigned';
    case TERMINATED = 'terminated';
    case DECEASED = 'deceased';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Karyawan Aktif',
            self::INACTIVE => 'Tidak Aktif / Suspend',
            self::RESIGNED => 'Mengundurkan Diri',
            self::TERMINATED => 'Diberhentikan (PHK)',
            self::DECEASED => 'Meninggal Dunia',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::INACTIVE => 'zinc',
            self::RESIGNED => 'warning',
            self::TERMINATED => 'danger',
            self::DECEASED => 'zinc',
        };
    }
}
