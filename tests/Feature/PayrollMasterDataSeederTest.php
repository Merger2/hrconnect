<?php

use App\Models\PayrollComponent;
use App\Models\Setting;
use Database\Seeders\PayrollComponentSeeder;
use Database\Seeders\SettingSeeder;

test('payroll indonesia master data seeders include new defaults', function () {
    $this->seed([
        SettingSeeder::class,
        PayrollComponentSeeder::class,
    ]);

    expect(PayrollComponent::query()->where('name', 'Tunjangan Jabatan Tetap')->exists())->toBeTrue()
        ->and(PayrollComponent::query()->where('name', 'Tunjangan Kinerja Tidak Tetap')->exists())->toBeTrue()
        ->and(PayrollComponent::query()->where('name', 'BPJS Kesehatan (1%)')->value('percentage'))->toBe('1.00')
        ->and(PayrollComponent::query()->where('name', 'BPJS Ketenagakerjaan JHT (2%)')->value('percentage'))->toBe('2.00')
        ->and(PayrollComponent::query()->where('name', 'BPJS Ketenagakerjaan JP (1%)')->value('percentage'))->toBe('1.00')
        ->and(PayrollComponent::query()->where('name', 'Potongan Karyawan')->exists())->toBeTrue()
        ->and(Setting::getValue('payroll.tax_method'))->toBe('pph21_ter')
        ->and(Setting::getValue('payroll.thr_prorata_enabled'))->toBe('1')
        ->and(Setting::getValue('payroll.bank_instruction_format'))->toBe('generic_csv');
});
