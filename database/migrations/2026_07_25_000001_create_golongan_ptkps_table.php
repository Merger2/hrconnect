<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('golongan_ptkps', function (Blueprint $table) {
            $table->id();

            // --- IDENTITAS ---
            $table->string('kode', 10)->unique()->comment('Kode PTKP, e.g. TK/0, K/1, K/2, K/3');
            $table->string('nama')->comment('Nama golongan PTKP');

            // --- NILAI PTKP ---
            $table->decimal('ptkp_tahun', 14, 2)->comment('Nilai PTKP per tahun (Rp)');
            $table->decimal('ptkp_bulan', 12, 2)->comment('Nilai PTKP per bulan (Rp)');

            // --- FOREIGN KEYS ---
            $table->foreignId('kategori_ter_id')->nullable()->constrained('kategori_ter')->nullOnDelete();

            // --- STATUS ---
            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });

        // Seed data: Standard Indonesian PTKP values (2024/2025)
        $ptkpData = [
            // Kode, Nama, PTKP Tahun, PTKP Bulan, Kategori TER
            ['TK/0', 'Tidak Kawin tanpa tanggungan', 54_000_000, 4_500_000, 'A'],
            ['TK/1', 'Tidak Kawin dengan 1 tanggungan', 58_500_000, 4_875_000, 'A'],
            ['TK/2', 'Tidak Kawin dengan 2 tanggungan', 63_000_000, 5_250_000, 'B'],
            ['TK/3', 'Tidak Kawin dengan 3 tanggungan', 67_500_000, 5_625_000, 'B'],
            ['K/0', 'Kawin tanpa tanggungan', 58_500_000, 4_875_000, 'A'],
            ['K/1', 'Kawin dengan 1 tanggungan', 63_000_000, 5_250_000, 'B'],
            ['K/2', 'Kawin dengan 2 tanggungan', 67_500_000, 5_625_000, 'B'],
            ['K/3', 'Kawin dengan 3 tanggungan', 72_000_000, 6_000_000, 'C'],
        ];

        $kategoriTerMap = DB::table('kategori_ter')->pluck('id', 'kode');

        foreach ($ptkpData as [$kode, $nama, $tahun, $bulan, $terKode]) {
            DB::table('golongan_ptkps')->insert([
                'kode' => $kode,
                'nama' => $nama,
                'ptkp_tahun' => $tahun,
                'ptkp_bulan' => $bulan,
                'kategori_ter_id' => $kategoriTerMap->get($terKode),
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('golongan_ptkps');
    }
};
