<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder dasar untuk memastikan setting aplikasi ada
        Setting::updateOrCreate(
            ['key' => 'app_name'],
            ['value' => 'HRConnect Enterprise']
        );

        Setting::updateOrCreate(
            ['key' => 'timezone'],
            ['value' => 'Asia/Jakarta']
        );

        Setting::updateOrCreate(['key' => 'payroll.tax_method'], ['value' => 'pph21_ter']);
        Setting::updateOrCreate(['key' => 'payroll.thr_prorata_enabled'], ['value' => '1']);
        Setting::updateOrCreate(['key' => 'payroll.bank_instruction_format'], ['value' => 'generic_csv']);

        // Branding settings — also used by MailBranding, payslip PDF, and email templates
        Setting::updateOrCreate(['key' => 'app.company_name'], [
            'value' => 'PT Daya Cipta Mandiri Solusi',
            'group' => 'general',
            'type' => 'text',
            'description' => 'Nama perusahaan untuk tampilan di email, payslip, dan dokumen',
        ]);
        Setting::updateOrCreate(['key' => 'app.company_address'], [
            'value' => 'Jl. Pegambiran No.292 B, RT.15/RW.8, Rawamangun, Kec. Pulo Gadung, Kota Jakarta Timur, Daerah Khusus Ibukota Jakarta 13220',
            'group' => 'general',
            'type' => 'textarea',
            'description' => 'Alamat perusahaan',
        ]);
        Setting::updateOrCreate(['key' => 'app.company_phone'], [
            'value' => '',
            'group' => 'general',
            'type' => 'text',
            'description' => 'Nomor telepon perusahaan',
        ]);
        Setting::updateOrCreate(['key' => 'app.company_website'], [
            'value' => '',
            'group' => 'general',
            'type' => 'text',
            'description' => 'Website perusahaan',
        ]);

        $this->command->info('SettingSeeder completed.');
    }
}
