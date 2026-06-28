<?php

declare(strict_types=1);

namespace App\Services\Payroll;

class LemburService
{
    private const HOURLY_DIVISOR = 173;

    public function calculateInsentif(
        float $gajiPokok,
        float $tunjanganTetap,
        int $totalMinutes,
        bool $isHoliday = false,
    ): float {
        $totalHours = (int) ceil($totalMinutes / 60);

        if ($totalHours <= 0) {
            return 0;
        }

        $upahPerJam = ($gajiPokok + $tunjanganTetap) / self::HOURLY_DIVISOR;

        if ($isHoliday) {
            return $this->holidayRate($upahPerJam, $totalHours);
        }

        return $this->weekdayRate($upahPerJam, $totalHours);
    }

    private function weekdayRate(float $upahPerJam, int $hours): float
    {
        $total = $upahPerJam * 1.5 + $upahPerJam * 2 * ($hours - 1);

        return round($total);
    }

    private function holidayRate(float $upahPerJam, int $hours): float
    {
        $total = 0.0;

        for ($h = 1; $h <= $hours; $h++) {
            if ($h <= 8) {
                $total += $upahPerJam * 2;
            } elseif ($h === 9) {
                $total += $upahPerJam * 3;
            } else {
                $total += $upahPerJam * 4;
            }
        }

        return round($total);
    }
}
