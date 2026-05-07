<?php

namespace App\Enums;

enum CompanySettingType: string
{
    case GEODATA = 'geodata';
    case BRANDING = 'branding';
    case ATTENDANCE = 'attendance';
    case LEAVE = 'leave';
    case PAYROLL = 'payroll';
    case SYSTEM = 'system';

    public function label(): string
    {
        return match ($this) {
            self::GEODATA => 'Data Lokasi',
            self::BRANDING => 'Branding',
            self::ATTENDANCE => 'Absensi',
            self::LEAVE => 'Cuti',
            self::PAYROLL => 'Penggajian',
            self::SYSTEM => 'Sistem',
        };
    }
}
