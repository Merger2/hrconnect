<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case ON_TIME = 'on_time';
    case PRESENT = 'present';
    case LATE = 'late';
    case EARLY = 'early';
    case ABSENT = 'absent';
    case PERMISSION = 'permission';
    case HOLIDAY = 'holiday';
    case MISSED_CLOCK_IN = 'missed_clock_in';
    case MISSED_CLOCK_OUT = 'missed_clock_out';
    case EXCUSED = 'excused';
    case SICK = 'sick';

    public function label(): string
    {
        return match ($this) {
            self::ON_TIME, self::PRESENT => 'Tepat Waktu',
            self::LATE => 'Terlambat',
            self::EARLY => 'Pulang Cepat',
            self::ABSENT => 'Mangkir (Alfa)',
            self::PERMISSION => 'Izin',
            self::HOLIDAY => 'Hari Libur',
            self::MISSED_CLOCK_IN => 'Lupa Absen Masuk',
            self::MISSED_CLOCK_OUT => 'Lupa Absen Pulang',
            self::EXCUSED => 'Izin',
            self::SICK => 'Sakit',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ON_TIME, self::PRESENT, self::HOLIDAY, self::PERMISSION => 'success',
            self::LATE, self::EARLY => 'warning',
            self::ABSENT, self::MISSED_CLOCK_IN, self::MISSED_CLOCK_OUT => 'danger',
            self::EXCUSED, self::SICK => 'warning',
        };
    }

    public function isPenalty(): bool
    {
        return match ($this) {
            self::ABSENT, self::MISSED_CLOCK_IN, self::MISSED_CLOCK_OUT => true,
            default => false,
        };
    }

    public function short(): string
    {
        return match ($this) {
            self::ON_TIME, self::PRESENT => 'H',
            self::LATE => 'T',
            self::EXCUSED, self::PERMISSION => 'I',
            self::SICK => 'S',
            self::ABSENT => 'A',
            default => '-',
        };
    }

    public function dot(): string
    {
        return match ($this) {
            self::ON_TIME, self::PRESENT, self::HOLIDAY => 'bg-emerald-500',
            self::LATE, self::EARLY => 'bg-amber-500',
            self::EXCUSED, self::PERMISSION => 'bg-sky-500',
            self::SICK => 'bg-purple-500',
            self::ABSENT, self::MISSED_CLOCK_IN, self::MISSED_CLOCK_OUT => 'bg-rose-500',
            default => 'bg-slate-400',
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::ON_TIME, self::PRESENT, self::HOLIDAY, self::PERMISSION => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/20 dark:text-emerald-300',
            self::LATE, self::EARLY => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/20 dark:text-amber-300',
            self::EXCUSED => 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-900/20 dark:text-sky-300',
            self::SICK => 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-900/20 dark:text-purple-300',
            self::ABSENT, self::MISSED_CLOCK_IN, self::MISSED_CLOCK_OUT => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-900/20 dark:text-rose-300',
            default => 'bg-slate-50 text-slate-600 ring-slate-500/10 dark:bg-slate-800 dark:text-slate-400',
        };
    }

    public function cell(): string
    {
        return match ($this) {
            self::ON_TIME, self::PRESENT, self::HOLIDAY, self::PERMISSION => 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 dark:bg-emerald-900/30 dark:text-emerald-300',
            self::LATE, self::EARLY => 'bg-amber-50 text-amber-700 ring-amber-600/20 dark:bg-amber-900/30 dark:text-amber-300',
            self::EXCUSED => 'bg-sky-50 text-sky-700 ring-sky-600/20 dark:bg-sky-900/30 dark:text-sky-300',
            self::SICK => 'bg-purple-50 text-purple-700 ring-purple-600/20 dark:bg-purple-900/30 dark:text-purple-300',
            self::ABSENT, self::MISSED_CLOCK_IN, self::MISSED_CLOCK_OUT => 'bg-rose-50 text-rose-700 ring-rose-600/20 dark:bg-rose-900/30 dark:text-rose-300',
            default => 'bg-slate-50 text-slate-600 ring-slate-500/10 dark:bg-slate-800 dark:text-slate-400',
        };
    }

    public static function fromLegacy(string $value): self
    {
        return match ($value) {
            'present' => self::ON_TIME,
            default => self::tryFrom($value) ?? self::ON_TIME,
        };
    }
}
