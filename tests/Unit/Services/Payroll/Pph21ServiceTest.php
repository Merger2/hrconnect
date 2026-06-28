<?php

use App\Services\Payroll\Pph21Service;

describe('Pph21Service', function () {
    it('returns zero when bruto is below PTKP', function () {
        $service = new Pph21Service;
        expect($service->calculateMonthly(4_000_000 * 12))->toBe(0.0);
    });

    it('calculates 5% bracket correctly', function () {
        $service = new Pph21Service;
        $monthlyTax = $service->calculateMonthly(7_000_000 * 12);
        $expectedMonthly = round((((7_000_000 * 12) - 54_000_000) * 0.05) / 12);
        expect($monthlyTax)->toBe($expectedMonthly);
    });

    it('calculates 15% bracket correctly', function () {
        $service = new Pph21Service;
        $annual = (20_000_000 * 12) - 54_000_000;
        $expectedAnnual = (60_000_000 * 0.05) + (($annual - 60_000_000) * 0.15);
        expect($service->calculateMonthly(20_000_000 * 12))->toBe(round($expectedAnnual / 12));
    });

    it('calculates 30% bracket correctly', function () {
        $service = new Pph21Service;
        $brutoAnnual = 100_000_000 * 12;
        $pkp = $brutoAnnual - 54_000_000;
        $remainingAfterThreeTiers = $pkp - 60_000_000 - 190_000_000 - 250_000_000;
        $expectedAnnual = (60_000_000 * 0.05) + (190_000_000 * 0.15) + (250_000_000 * 0.25) + ($remainingAfterThreeTiers * 0.30);
        expect($service->calculateMonthly($brutoAnnual))->toBe(round($expectedAnnual / 12));
    });

    it('returns breakdown with brackets', function () {
        $service = new Pph21Service;
        $result = $service->calculateWithBreakdown(10_000_000 * 12);

        expect($result)->toHaveKeys(['annualized_bruto', 'ptkp', 'pkp', 'pph21_annual', 'pph21_monthly', 'brackets']);
        expect($result['brackets'])->toHaveCount(4);
        expect($result['annualized_bruto'])->toBe(120_000_000.0);
        expect($result['pkp'])->toBe(66_000_000.0);
        expect($result['pph21_monthly'])->toBe(round(((60_000_000 * 0.05) + (6_000_000 * 0.15)) / 12));
    });

    it('handles zero bruto', function () {
        $service = new Pph21Service;
        expect($service->calculateMonthly(0))->toBe(0.0);
    });
});
