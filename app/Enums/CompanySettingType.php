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
            self::GEODATA => 'Lokasi & GPS',
            self::BRANDING => 'Branding Perusahaan',
            self::ATTENDANCE => 'Aturan Presensi',
            self::LEAVE => 'Aturan Cuti',
            self::PAYROLL => 'Konfigurasi Penggajian',
            self::SYSTEM => 'Sistem & AI',
        };
    }
}
