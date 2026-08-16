<?php

namespace Database\Seeders;

use App\Models\ReimbursementCategory;
use Illuminate\Database\Seeder;

/**
 * Kategori reimburse default (global, company_id null = berlaku untuk semua
 * perusahaan). Sinkron dengan daftar jenis hardcoded di
 * app/Livewire/User/ReimbursementPage.php — keputusan Fikih 2026-08-06:
 * tanpa medical/optical/dental (BPJS Kesehatan sudah menutup perawatan
 * dasar), fokus kategori operasional kerja.
 */
class ReimbursementCategorySeeder extends Seeder
{
    /** @var array<int, array{code: string, name: string}> */
    private const CATEGORIES = [
        ['code' => 'transport', 'name' => 'Transportasi'],
        ['code' => 'meals', 'name' => 'Makan & Jamuan'],
        ['code' => 'lodging', 'name' => 'Akomodasi'],
        ['code' => 'communication', 'name' => 'Komunikasi'],
        ['code' => 'education', 'name' => 'Pendidikan & Pelatihan'],
        ['code' => 'equipment', 'name' => 'Perlengkapan Kerja'],
        ['code' => 'other', 'name' => 'Lainnya'],
    ];

    public function run(): void
    {
        foreach (self::CATEGORIES as $category) {
            ReimbursementCategory::updateOrCreate(
                ['code' => $category['code'], 'company_id' => null],
                [
                    'name' => $category['name'],
                    'is_active' => true,
                ]
            );
        }
    }
}
