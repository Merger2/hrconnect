<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            // Foreign Key ke tabel companies
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->foreignId('province_id')->nullable()->constrained('indonesia_provinces')->restrictOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('indonesia_cities')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('indonesia_districts')->restrictOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('indonesia_villages')->restrictOnDelete();
            $table->string('postal_code', 10)->nullable();
            $table->text('address_detail');
            $table->boolean('is_main')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('latitude', 10, 8)->nullable()->comment('Titik Y Pusat Kantor');
            $table->decimal('longitude', 11, 8)->nullable()->comment('Titik X Pusat Kantor');
            $table->integer('radius')->default(100)->comment('Batas toleransi absen dalam meter');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
