<?php

namespace App\Enums;

enum EmploymentType: string
{
    case PERMANENT = 'permanent';
    case CONTRACT = 'contract';
    case PROBATION = 'probation'; // Ditambahkan untuk handle limitasi cuti
    case INTERN = 'intern';

    public function label(): string
    {
        return match ($this) {
            self::PERMANENT => 'Karyawan Tetap',
            self::CONTRACT => 'Karyawan Kontrak (PKWT)',
            self::PROBATION => 'Masa Percobaan',
            self::INTERN => 'Magang/Intern',
        };
    }

    /**
     * Mengecek apakah tipe karyawan ini berhak didaftarkan BPJS.
     * Anak magang tidak ditanggung BPJS sesuai aturan perusahaan.
     */
    public function hasBPJS(): bool
    {
        return match ($this) {
            self::INTERN => false,
            default => true,
        };
    }

    /**
     * Mengecek apakah tipe karyawan ini terkena potongan PPh 21 TER.
     */
    public function hasPPh21(): bool
    {
        return match ($this) {
            self::INTERN => false,
            default => true,
        };
    }

    /**
     * Mengecek apakah karyawan memiliki saldo/kuota cuti tahunan.
     */
    public function hasLeaveQuota(): bool
    {
        return match ($this) {
            self::INTERN => false,
            default => true,
        };
    }

    /**
     * Mengecek apakah karyawan diizinkan memakai cutinya (Probation Block)
     */
    public function canTakeAnnualLeave(): bool
    {
        return match ($this) {
            self::INTERN, self::PROBATION => false,
            default => true,
        };
    }
}
