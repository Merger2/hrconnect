<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\EmployeeDocumentTemplate;
use App\Models\EmployeeDocumentType;
use Illuminate\Database\Seeder;

class EmployeeDocumentTemplateSeeder extends Seeder
{
    /**
     * Tipe dokumen default + template aktifnya.
     *
     * @return array<int, array{code:string,slug:string,name:string,category:string}>
     */
    private function defaultTypes(): array
    {
        return [
            ['code' => 'employment_certificate', 'slug' => 'employment-certificate', 'name' => 'Surat Keterangan Kerja', 'category' => 'hr'],
            ['code' => 'salary_statement', 'slug' => 'salary-statement', 'name' => 'Surat Keterangan Gaji', 'category' => 'finance'],
            ['code' => 'npwp', 'slug' => 'npwp', 'name' => 'NPWP', 'category' => 'finance'],
            ['code' => 'bank_letter', 'slug' => 'bank-letter', 'name' => 'Surat Referensi Bank', 'category' => 'finance'],
            ['code' => 'visa_letter', 'slug' => 'visa-letter', 'name' => 'Surat Keterangan Visa', 'category' => 'hr'],
            ['code' => 'referral_letter', 'slug' => 'referral-letter', 'name' => 'Surat Rekomendasi', 'category' => 'hr'],
            ['code' => 'skck', 'slug' => 'skck', 'name' => 'SKCK', 'category' => 'legal'],
        ];
    }

    public function run(): void
    {
        foreach ($this->defaultTypes() as $typeData) {
            $type = EmployeeDocumentType::query()->updateOrCreate(
                ['slug' => $typeData['slug']],
                [
                    'code' => $typeData['code'],
                    'name' => $typeData['name'],
                    'category' => $typeData['category'],
                    'is_active' => true,
                    'employee_requestable' => true,
                    'admin_requestable' => true,
                    'requires_employee_upload' => false,
                    'auto_generate_enabled' => true,
                ],
            );

            // Pastikan minimal 2 template: 1 aktif + 1 cadangan.
            if ($type->templates()->count() < 2) {
                EmployeeDocumentTemplate::query()->create([
                    'document_type_id' => $type->id,
                    'name' => $type->name.' — Template Aktif',
                    'content' => '<h2>{{ employee.name }}</h2><p>Surat ini diterbitkan untuk keperluan resmi.</p>',
                    'variables' => ['heading', 'opening', 'closing'],
                    'paper_size' => 'a4',
                    'orientation' => 'portrait',
                    'is_active' => true,
                ]);

                EmployeeDocumentTemplate::query()->create([
                    'document_type_id' => $type->id,
                    'name' => $type->name.' — Template Cadangan',
                    'content' => '<h2>{{ employee.name }}</h2><p>Template alternatif untuk keperluan lain.</p>',
                    'variables' => ['heading', 'opening', 'closing'],
                    'paper_size' => 'a4',
                    'orientation' => 'portrait',
                    'is_active' => false,
                ]);
            }

            // Normalisasi: pastikan tepat satu template aktif.
            $active = $type->templates()->where('is_active', true)->first();
            if ($active) {
                $type->templates()->where('is_active', true)->where('id', '!=', $active->id)->update(['is_active' => false]);
            } else {
                $type->templates()->first()?->update(['is_active' => true]);
            }
        }
    }
}
