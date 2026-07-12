<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\BpjsConfig;

class BpjsService
{
    public function calculate(float $gajiPokok, float $tunjanganTetap = 0): array
    {
        $configs = BpjsConfig::cachedAll();

        $components = [];
        $total = 0;

        foreach ($configs as $config) {
            // B-30: JHT hanya berdasarkan gaji pokok (PP 46/2015 Pasal 3),
            // bukan gaji pokok + tunjangan tetap seperti komponen BPJS lain
            $dasar = $config['name'] === 'jht' ? $gajiPokok : $gajiPokok + $tunjanganTetap;
            $ceiling = $config['ceiling'];
            $base = $ceiling !== null ? min($dasar, $ceiling) : $dasar;
            $amount = round($base * $config['employee_rate']);

            $components[] = [
                'name' => $config['name'],
                'rate' => $config['employee_rate'],
                'base' => $base,
                'ceiling' => $ceiling,
                'amount' => $amount,
            ];

            $total += $amount;
        }

        return [
            'components' => $components,
            'total' => $total,
        ];
    }
}
