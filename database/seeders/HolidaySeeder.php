<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $holidays = [
            ['date' => '2026-01-01', 'name' => 'Tahun Baru Masehi'],
            ['date' => '2026-01-29', 'name' => 'Isra Mi\'raj'],
            ['date' => '2026-01-30', 'name' => 'Isra Mi\'raj (Cuti Bersama)'],
            ['date' => '2026-02-19', 'name' => 'Imlek'],
            ['date' => '2026-03-02', 'name' => 'Nyepi'],
            ['date' => '2026-03-20', 'name' => 'Wafat Isa Almasih'],
            ['date' => '2026-03-21', 'name' => 'Wafat Isa Almasih (Cuti Bersama)'],
            ['date' => '2026-03-29', 'name' => 'Hari Suci Paskah'],
            ['date' => '2026-03-30', 'name' => 'Cuti Bersama Paskah'],
            ['date' => '2026-03-31', 'name' => 'Cuti Bersama Paskah'],
            ['date' => '2026-04-01', 'name' => 'Cuti Bersama Paskah'],
            ['date' => '2026-04-02', 'name' => 'Cuti Bersama Paskah'],
            ['date' => '2026-04-03', 'name' => 'Waisak'],
            ['date' => '2026-04-14', 'name' => 'Isra Mi\'raj (Cuti Bersama)'],
            ['date' => '2026-04-17', 'name' => 'Jumat Agung'],
            ['date' => '2026-04-20', 'name' => 'Hari Raya Idul Fitri'],
            ['date' => '2026-04-21', 'name' => 'Hari Raya Idul Fitri'],
            ['date' => '2026-04-22', 'name' => 'Cuti Bersama Idul Fitri'],
            ['date' => '2026-04-23', 'name' => 'Cuti Bersama Idul Fitri'],
            ['date' => '2026-04-24', 'name' => 'Cuti Bersama Idul Fitri'],
            ['date' => '2026-05-01', 'name' => 'Hari Buruh'],
            ['date' => '2026-05-12', 'name' => 'Hari Raya Waisak'],
            ['date' => '2026-05-14', 'name' => 'Kenaikan Isa Almasih'],
            ['date' => '2026-05-15', 'name' => 'Cuti Bersama Kenaikan Isa Almasih'],
            ['date' => '2026-05-26', 'name' => 'Hari Raya Idul Adha'],
            ['date' => '2026-06-17', 'name' => 'Tahun Baru Islam'],
            ['date' => '2026-06-18', 'name' => 'Cuti Bersama Tahun Baru Islam'],
            ['date' => '2026-08-17', 'name' => 'Hari Kemerdekaan RI'],
            ['date' => '2026-09-05', 'name' => 'Maulid Nabi'],
            ['date' => '2026-09-07', 'name' => 'Cuti Bersama Maulid Nabi'],
            ['date' => '2026-11-25', 'name' => 'Hari Toleransi Internasional'],
            ['date' => '2026-12-25', 'name' => 'Hari Natal'],
            ['date' => '2026-12-26', 'name' => 'Cuti Bersama Natal'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::firstOrCreate(
                ['date' => $holiday['date']],
                [
                    'name' => $holiday['name'],
                    'is_active' => true,
                ]
            );
        }

        $this->command?->info('Holidays seeded: '.count($holidays).' holidays for 2026');
    }
}
