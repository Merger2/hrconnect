<?php

use App\Services\Payroll\LemburService;

describe('LemburService', function () {
    it('calculates weekday overtime 1 hour', function () {
        $service = new LemburService;
        $upahPerJam = (5_000_000 + 1_000_000) / 173;
        $expected = round($upahPerJam * 1.5);
        expect($service->calculateInsentif(5_000_000, 1_000_000, 60, false))->toBe($expected);
    });

    it('calculates weekday overtime 3 hours', function () {
        $service = new LemburService;
        $upahPerJam = (5_000_000 + 1_000_000) / 173;
        $expected = round(($upahPerJam * 1.5) + ($upahPerJam * 2) + ($upahPerJam * 2));
        expect($service->calculateInsentif(5_000_000, 1_000_000, 180, false))->toBe($expected);
    });

    it('calculates holiday overtime first 8 hours at 2x', function () {
        $service = new LemburService;
        $upahPerJam = (5_000_000 + 0) / 173;
        $expected = round($upahPerJam * 2 * 8);
        expect($service->calculateInsentif(5_000_000, 0, 8 * 60, true))->toBe($expected);
    });

    it('calculates holiday overtime 10 hours with progressive rates', function () {
        $service = new LemburService;
        $upahPerJam = (5_000_000 + 0) / 173;
        $expected = round(($upahPerJam * 2 * 8) + ($upahPerJam * 3 * 1) + ($upahPerJam * 4 * 1));
        expect($service->calculateInsentif(5_000_000, 0, 10 * 60, true))->toBe($expected);
    });

    it('returns zero for zero minutes', function () {
        $service = new LemburService;
        expect($service->calculateInsentif(5_000_000, 0, 0, false))->toBe(0.0);
    });
});
