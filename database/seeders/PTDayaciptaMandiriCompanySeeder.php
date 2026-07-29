<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;

class PTDayaciptaMandiriCompanySeeder extends Seeder
{
    public function run(): void
    {
        Company::firstOrCreate(
            ['code' => 'DKMS-2025'],
            [
                'name' => 'PT Daya Cipta Mandiri Solusi',
                'phone' => '+62 21 38859238',
                'email' => 'contact@dayaciptamandiri.com',
                'website' => 'https://dayaciptamandiri.com',
                'is_active' => true,
            ]
        );
    }
}
