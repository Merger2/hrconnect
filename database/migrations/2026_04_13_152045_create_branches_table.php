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
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->string('name');
            $table->text('address');
            $table->boolean('is_main')->default(false);
            $table->boolean('is_active')->default(true);
            $table->decimal('latitude', 10, 7)->nullable()->comment('Titik Y Pusat Kantor');
            $table->decimal('longitude', 10, 7)->nullable()->comment('Titik X Pusat Kantor');
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
