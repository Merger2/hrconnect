<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Rename & change type of Laravolt FK columns to Wilayah kode strings
            // province_id (bigint) → provinsi_kode (varchar 13, nullable)
            $table->string('provinsi_kode', 13)->nullable()->after('city_id');
            $table->string('kabupaten_kode', 13)->nullable()->after('provinsi_kode');
            $table->string('kecamatan_kode', 13)->nullable()->after('kabupaten_kode');
            $table->string('kelurahan_kode', 13)->nullable()->after('kecamatan_kode');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn(['provinsi_kode', 'kabupaten_kode', 'kecamatan_kode', 'kelurahan_kode']);
        });
    }
};
