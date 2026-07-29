<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('golongan_ptkp', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama');
            $table->decimal('ptkp_tahun', 15, 2);
            $table->decimal('ptkp_bulan', 15, 2);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        DB::table('golongan_ptkp')->insert([
            ['kode' => 'TK/0', 'nama' => 'Tidak Kawin Tanpa Anak', 'ptkp_tahun' => 54000000, 'ptkp_bulan' => 4500000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'K/0', 'nama' => 'Kawin Tanpa Anak', 'ptkp_tahun' => 58500000, 'ptkp_bulan' => 4875000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'K/1', 'nama' => 'Kawin 1 Anak', 'ptkp_tahun' => 63000000, 'ptkp_bulan' => 5250000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'K/2', 'nama' => 'Kawin 2 Anak', 'ptkp_tahun' => 67500000, 'ptkp_bulan' => 5625000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'K/3', 'nama' => 'Kawin 3 Anak', 'ptkp_tahun' => 72000000, 'ptkp_bulan' => 6000000, 'is_active' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('golongan_ptkp');
    }
};
