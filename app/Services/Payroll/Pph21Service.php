<?php

declare(strict_types=1);

namespace App\Services\Payroll;

class Pph21Service
{
    private const PTKP_ANNUAL = 54_000_000;

    private const TIERS = [
        ['size' => 60_000_000, 'rate' => 0.05],
        ['size' => 190_000_000, 'rate' => 0.15],
        ['size' => 250_000_000, 'rate' => 0.25],
        ['size' => null, 'rate' => 0.30],
    ];

    public function calculateMonthly(float $annualizedBruto, float $ptkp = self::PTKP_ANNUAL): float
    {
        $pkp = max(0, $annualizedBruto - $ptkp);

        if ($pkp <= 0) {
            return 0;
        }

        $annualTax = 0;
        $remaining = $pkp;

        foreach (self::TIERS as $tier) {
            $tierSize = $tier['size'];
            $tierAmount = $tierSize !== null ? min($remaining, $tierSize) : $remaining;

            if ($tierAmount <= 0) {
                break;
            }

            $annualTax += $tierAmount * $tier['rate'];
            $remaining -= $tierAmount;

            if ($remaining <= 0) {
                break;
            }
        }

        return round($annualTax / 12);
    }

    public function calculateWithBreakdown(float $annualizedBruto, float $ptkp = self::PTKP_ANNUAL): array
    {
        $pkp = max(0, $annualizedBruto - $ptkp);

        $brackets = [];
        $annualTax = 0;
        $remaining = $pkp;

        foreach (self::TIERS as $i => $tier) {
            $tierSize = $tier['size'];
            $tierAmount = $tierSize !== null ? min($remaining, $tierSize) : $remaining;

            if ($tierAmount <= 0) {
                $brackets[] = [
                    'tier' => $i + 1,
                    'amount' => 0,
                    'rate' => $tier['rate'],
                    'tax' => 0,
                ];

                continue;
            }

            $tax = $tierAmount * $tier['rate'];
            $annualTax += $tax;
            $remaining -= $tierAmount;

            $brackets[] = [
                'tier' => $i + 1,
                'amount' => $tierAmount,
                'rate' => $tier['rate'],
                'tax' => round($tax),
            ];
        }

        return [
            'annualized_bruto' => $annualizedBruto,
            'ptkp' => $ptkp,
            'pkp' => $pkp,
            'pph21_annual' => round($annualTax),
            'pph21_monthly' => round($annualTax / 12),
            'brackets' => $brackets,
        ];
    }
}
