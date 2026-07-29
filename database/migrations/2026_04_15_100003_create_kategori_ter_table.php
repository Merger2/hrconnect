<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kategori_ter', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        DB::table('kategori_ter')->insert([
            ['kode' => 'A', 'nama' => 'Pegawai dgn tanggungan 0-1', 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'B', 'nama' => 'Pegawai dgn tanggungan 2-3', 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'C', 'nama' => 'Pegawai dgn tanggungan 4+', 'created_at' => now(), 'updated_at' => now()],
            ['kode' => 'D', 'nama' => 'Pegawai harian lepas', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('kategori_ter');
    }
};
