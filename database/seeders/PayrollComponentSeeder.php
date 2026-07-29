<?php

namespace Database\Seeders;

use App\Models\PayrollComponent;
use Illuminate\Database\Seeder;

class PayrollComponentSeeder extends Seeder
{
    public function run(): void
    {
        $components = [
            [
                'name' => 'Tunjangan Jabatan Tetap',
                'code' => 'tunj_jabatan_tetap',
                'type' => 'allowance',
                'description' => 'Tunjangan jabatan struktural/fungsional tetap',
                'is_taxable' => true,
                'is_bpjs_applicable' => true,
                'is_active' => true,
            ],
            [
                'name' => 'Tunjangan Kinerja Tidak Tetap',
                'code' => 'tunj_kinerja_tidak_tetap',
                'type' => 'allowance',
                'description' => 'Tunjangan kinerja bulanan tidak tetap',
                'is_taxable' => true,
                'is_bpjs_applicable' => false,
                'is_active' => true,
            ],
            [
                'name' => 'BPJS Kesehatan (1%)',
                'code' => 'bpjs_kesehatan_1',
                'type' => 'deduction',
                'description' => 'Iuran BPJS Kesehatan 1%',
                'is_taxable' => false,
                'is_bpjs_applicable' => false,
                'is_active' => true,
                'percentage' => '1.00',
            ],
            [
                'name' => 'BPJS Ketenagakerjaan JHT (2%)',
                'code' => 'bpjs_tk_jht_2',
                'type' => 'deduction',
                'description' => 'Iuran BPJS JHT 2%',
                'is_taxable' => false,
                'is_bpjs_applicable' => false,
                'is_active' => true,
                'percentage' => '2.00',
            ],
            [
                'name' => 'BPJS Ketenagakerjaan JP (1%)',
                'code' => 'bpjs_tk_jp_1',
                'type' => 'deduction',
                'description' => 'Iuran BPJS JP 1%',
                'is_taxable' => false,
                'is_bpjs_applicable' => false,
                'is_active' => true,
                'percentage' => '1.00',
            ],
            [
                'name' => 'Potongan Karyawan',
                'code' => 'potongan_karyawan',
                'type' => 'deduction',
                'description' => 'Potongan umum karyawan',
                'is_taxable' => false,
                'is_bpjs_applicable' => false,
                'is_active' => true,
            ],
        ];

        foreach ($components as $component) {
            PayrollComponent::firstOrCreate(['code' => $component['code']], $component);
        }

        $this->command->info('PayrollComponentSeeder completed. Total: '.count($components));
    }
}
