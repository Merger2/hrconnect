<?php

namespace App\Enums;

enum ApprovalLevel: string
{
    case L1_SUPERVISOR = 'l1_supervisor';
    case L2_MANAGER = 'l2_manager';
    case L3_HRD = 'l3_hrd';
    case L4_DIRECTOR = 'l4_director';

    public function label(): string
    {
        return match ($this) {
            self::L1_SUPERVISOR => 'Supervisor Langsung',
            self::L2_MANAGER => 'Manager Departemen',
            self::L3_HRD => 'HRD Manager',
            self::L4_DIRECTOR => 'Direktur',
        };
    }

    public function order(): int
    {
        return match ($this) {
            self::L1_SUPERVISOR => 1,
            self::L2_MANAGER => 2,
            self::L3_HRD => 3,
            self::L4_DIRECTOR => 4,
        };
    }
}
