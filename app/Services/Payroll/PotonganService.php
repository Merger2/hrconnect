<?php

declare(strict_types=1);

namespace App\Services\Payroll;

class PotonganService
{
    private const WORKING_DAYS = 22;

    public function calculateAlfa(
        float $gajiPokok,
        float $tunjanganJabatan,
        float $tunjanganMakanHarian,
        float $tunjanganTransportHarian,
        int $hariAlfa,
    ): float {
        if ($hariAlfa <= 0) {
            return 0;
        }

        $upahPerHari = ($gajiPokok + $tunjanganJabatan) / self::WORKING_DAYS;
        $tunjanganHarian = $tunjanganMakanHarian + $tunjanganTransportHarian;

        return round(($upahPerHari + $tunjanganHarian) * $hariAlfa);
    }
}
