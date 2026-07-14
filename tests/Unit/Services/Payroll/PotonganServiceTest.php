<?php

use App\Services\Payroll\PotonganService;

describe('PotonganService', function () {
    it('calculates alfa deduction for 1 day', function () {
        $service = new PotonganService;
        $perHari = (5_000_000 + 1_000_000) / 22;
        $tunjanganHarian = 50_000 + 50_000;
        $expected = round(($perHari + $tunjanganHarian) * 1);
        expect($service->calculateAlfa(5_000_000, 1_000_000, 50_000, 50_000, 1))->toBe($expected);
    });

    it('calculates alfa deduction for 5 days', function () {
        $service = new PotonganService;
        $perHari = (5_000_000 + 1_000_000) / 22;
        $expected = round(($perHari + 0) * 5);
        expect($service->calculateAlfa(5_000_000, 1_000_000, 0, 0, 5))->toBe($expected);
    });

    it('returns zero for zero alpha days', function () {
        $service = new PotonganService;
        expect($service->calculateAlfa(5_000_000, 0, 0, 0, 0))->toBe(0.0);
    });
});
