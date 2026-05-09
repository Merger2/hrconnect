<?php

namespace App\Enums;

enum ApprovalLevel: int
{
    case L1_SUPERVISOR = 1;
    case L2_MANAGER = 2;
    case L3_HRD = 3;
    case L4_DIRECTOR = 4;

    public function label(): string
    {
        return match ($this) {
            self::L1_SUPERVISOR => 'Supervisor Langsung',
            self::L2_MANAGER => 'Manager Departemen',
            self::L3_HRD => 'HRD Manager',
            self::L4_DIRECTOR => 'Direktur',
        };
    }
}
