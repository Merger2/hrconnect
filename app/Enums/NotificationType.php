<?php

namespace App\Enums;

enum NotificationType: string
{
    case ATTENDANCE = 'attendance';
    case LEAVE = 'leave';
    case PAYROLL = 'payroll';
    case SYSTEM = 'system';
    case APPROVAL = 'approval';
    case REMINDER = 'reminder';

    public function label(): string
    {
        return match ($this) {
            self::ATTENDANCE => 'Absensi',
            self::LEAVE => 'Cuti',
            self::PAYROLL => 'Penggajian',
            self::SYSTEM => 'Sistem',
            self::APPROVAL => 'Persetujuan',
            self::REMINDER => 'Pengingat',
        };
    }
}
