<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah kategori_ter_id ke golongan_ptkp
        Schema::table('golongan_ptkp', function (Blueprint $table) {
            $table->foreignId('kategori_ter_id')->nullable()->constrained('kategori_ter');
        });

        // 2. Mapping golongan_ptkp ke kategori_ter berdasarkan PMK 168/2023
        DB::statement("UPDATE golongan_ptkp SET kategori_ter_id = (SELECT id FROM kategori_ter WHERE kode = 'A') WHERE kode IN ('TK/0')");
        DB::statement("UPDATE golongan_ptkp SET kategori_ter_id = (SELECT id FROM kategori_ter WHERE kode = 'B') WHERE kode IN ('K/0', 'K/1')");
        DB::statement("UPDATE golongan_ptkp SET kategori_ter_id = (SELECT id FROM kategori_ter WHERE kode = 'C') WHERE kode IN ('K/2', 'K/3')");

        // 3. Insert golongan_ptkp yang missing (TK/1, TK/2, TK/3)
        $catA = DB::table('kategori_ter')->where('kode', 'A')->value('id');
        $catB = DB::table('kategori_ter')->where('kode', 'B')->value('id');
        DB::table('golongan_ptkp')->insert([
            ['kode' => 'TK/1', 'nama' => 'Tidak Kawin 1 Anak', 'ptkp_tahun' => 58500000, 'ptkp_bulan' => 4875000, 'kategori_ter_id' => $catA, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'TK/2', 'nama' => 'Tidak Kawin 2 Anak', 'ptkp_tahun' => 63000000, 'ptkp_bulan' => 5250000, 'kategori_ter_id' => $catB, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'TK/3', 'nama' => 'Tidak Kawin 3 Anak', 'ptkp_tahun' => 67500000, 'ptkp_bulan' => 5625000, 'kategori_ter_id' => $catB, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 4. Drop FK constraint before dropping tarif_ter
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['tarif_ter_id']);
        });

        // 5. Drop old tarif_ter, recreate with quanta schema
        Schema::dropIfExists('tarif_ter');
        Schema::create('tarif_ter', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kategori_ter_id')->constrained('kategori_ter');
            $table->decimal('batas_bawah', 15, 2);
            $table->decimal('batas_atas', 15, 2)->nullable();
            $table->decimal('tarif', 5, 4);
            $table->timestamps();
        });

        // 6. Re-add FK constraint
        Schema::table('employees', function (Blueprint $table) {
            $table->foreign('tarif_ter_id')->references('id')->on('tarif_ter');
        });

        // 7. Drop duplicate tax_configs
        Schema::dropIfExists('tax_configs');

        // 8. Make kategori_ter_id not-null after data seeded
        Schema::table('golongan_ptkp', function (Blueprint $table) {
            $table->foreignId('kategori_ter_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('golongan_ptkp', function (Blueprint $table) {
            $table->dropForeign(['kategori_ter_id']);
            $table->dropColumn('kategori_ter_id');
        });

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['tarif_ter_id']);
        });

        Schema::dropIfExists('tarif_ter');
        Schema::create('tarif_ter', function (Blueprint $table) {
            $table->id();
            $table->decimal('penghasilan_min', 15, 2);
            $table->decimal('penghasilan_max', 15, 2)->nullable();
            $table->decimal('tarif_a', 5, 2);
            $table->decimal('tarif_b', 5, 2);
            $table->decimal('tarif_c', 5, 2);
            $table->timestamps();
        });

        Schema::create('tax_configs', function (Blueprint $table) {
            $table->id();
            $table->char('ter_category', 1);
            $table->decimal('min_income', 15, 2);
            $table->decimal('max_income', 15, 2);
            $table->decimal('rate', 5, 4);
            $table->decimal('effective_rate', 5, 4)->nullable();
            $table->timestamps();
        });
    }
};
