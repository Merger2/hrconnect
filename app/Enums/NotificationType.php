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
            self::ATTENDANCE => 'Info Presensi',
            self::LEAVE => 'Info Cuti',
            self::PAYROLL => 'Slip Gaji',
            self::SYSTEM => 'Sistem HRIS',
            self::APPROVAL => 'Butuh Persetujuan',
            self::REMINDER => 'Pengingat',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::ATTENDANCE => 'clock',
            self::LEAVE => 'calendar-days',
            self::PAYROLL => 'banknotes',
            self::SYSTEM => 'cog-6-tooth',
            self::APPROVAL => 'check-badge',
            self::REMINDER => 'bell-alert',
        };
    }
}
