<?php

use App\Enums\BpjsType;
use App\Services\Payroll\BpjsService;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    Cache::shouldReceive('remember')
        ->with('bpjs_configs', Mockery::type(DateTimeInterface::class), Mockery::type('callable'))
        ->andReturn([
            ['name' => BpjsType::KESEHATAN->value, 'employer_rate' => 0.04, 'employee_rate' => 0.01, 'ceiling' => 12_000_000.0],
            ['name' => BpjsType::JHT->value, 'employer_rate' => 0.037, 'employee_rate' => 0.02, 'ceiling' => null],
            ['name' => BpjsType::JP->value, 'employer_rate' => 0.02, 'employee_rate' => 0.01, 'ceiling' => 9_559_600.0],
            ['name' => BpjsType::JKK->value, 'employer_rate' => 0.0024, 'employee_rate' => 0.0, 'ceiling' => null],
            ['name' => BpjsType::JKM->value, 'employer_rate' => 0.003, 'employee_rate' => 0.0, 'ceiling' => null],
        ]);
});

describe('BpjsService', function () {
    it('calculates BPJS deductions for salary within all ceilings', function () {
        $service = new BpjsService;
        $result = $service->calculate(8_000_000);

        expect($result['components'])->toHaveCount(5);
        expect($result['total'])->toEqual(320000.0);
    });

    it('applies ceiling for Kesehatan (max 12jt)', function () {
        $service = new BpjsService;
        $result = $service->calculate(15_000_000);

        $kesehatan = collect($result['components'])->firstWhere('name', BpjsType::KESEHATAN->value);
        expect($kesehatan['base'])->toEqual(12_000_000.0);
        expect($kesehatan['amount'])->toEqual(120000.0);
    });

    it('applies ceiling for JP (max 9,559,600)', function () {
        $service = new BpjsService;
        $result = $service->calculate(15_000_000);

        $jp = collect($result['components'])->firstWhere('name', BpjsType::JP->value);
        expect($jp['base'])->toEqual(9_559_600.0);
        expect($jp['amount'])->toEqual(95596.0);
    });

    it('does not apply ceiling for JHT (unlimited)', function () {
        $service = new BpjsService;
        $result = $service->calculate(15_000_000);

        $jht = collect($result['components'])->firstWhere('name', BpjsType::JHT->value);
        expect($jht['ceiling'])->toBeNull();
        expect($jht['base'])->toEqual(15_000_000.0);
        expect($jht['amount'])->toEqual(300000.0);
    });

    it('includes tunjangan tetap in calculation base', function () {
        $service = new BpjsService;
        $result = $service->calculate(5_000_000, 2_000_000);

        expect($result['total'])->toBeGreaterThan($service->calculate(5_000_000)['total']);
    });

    it('returns zero for components with zero employee rate', function () {
        $service = new BpjsService;
        $result = $service->calculate(8_000_000);

        $jkk = collect($result['components'])->firstWhere('name', BpjsType::JKK->value);
        $jkm = collect($result['components'])->firstWhere('name', BpjsType::JKM->value);

        expect((float) $jkk['amount'])->toBe(0.0);
        expect((float) $jkm['amount'])->toBe(0.0);
    });

    it('returns components with correct structure', function () {
        $service = new BpjsService;
        $result = $service->calculate(5_000_000);

        expect($result)->toHaveKeys(['components', 'total']);
        foreach ($result['components'] as $component) {
            expect($component)->toHaveKeys(['name', 'rate', 'base', 'ceiling', 'amount']);
        }
    });
});
