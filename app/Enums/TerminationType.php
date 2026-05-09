<?php

namespace App\Enums;

enum TerminationType: string
{
    case RESIGN = 'resign';
    case DISMISSED = 'dismissed';
    case DECEASED = 'deceased';
    case CONTRACT_END = 'contract_end';

    public function label(): string
    {
        return match ($this) {
            self::RESIGN => 'Mengundurkan Diri',
            self::DISMISSED => 'PHK',
            self::DECEASED => 'Meninggal Dunia',
            self::CONTRACT_END => 'Akhir Kontrak',
        };
    }
}
