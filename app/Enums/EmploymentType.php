<?php

namespace App\Enums;

enum EmploymentType: string
{
    case FULLTIME = 'fulltime';
    case CONTRACT = 'contract';
    case INTERN = 'intern';

    public function label(): string
    {
        return match ($this) {
            self::FULLTIME => 'Karyawan Tetap',
            self::CONTRACT => 'Karyawan Kontrak',
            self::INTERN => 'Magang/Intern',
        };
    }

    public function hasBPJS(): bool
    {
        return !in_array($this, [self::INTERN]);
    }

    public function hasPPh21(): bool
    {
        return !in_array($this, [self::INTERN]);
    }

    public function hasLeaveQuota(): bool
    {
        return !in_array($this, [self::INTERN]);
    }
}
