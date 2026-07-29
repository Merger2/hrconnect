<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_required')->default(false);
            $table->string('icon', 50)->nullable();
            $table->timestamps();
        });

        DB::table('employee_document_types')->insert([
            ['name' => 'KTP', 'slug' => 'ktp', 'is_required' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'NPWP', 'slug' => 'npwp', 'is_required' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Ijazah', 'slug' => 'ijazah', 'is_required' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kartu Keluarga', 'slug' => 'kk', 'is_required' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sertifikat', 'slug' => 'sertifikat', 'is_required' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Kontrak Kerja', 'slug' => 'kontrak-kerja', 'is_required' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_document_types');
    }
};
