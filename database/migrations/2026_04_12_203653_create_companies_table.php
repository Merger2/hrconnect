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
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->string('website')->nullable();
            $table->text('npwp')->nullable();
            $table->string('code')->unique();
            $table->string('logo', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->string('slug')->nullable();
            $table->unique('slug');
            $table->string('status')->default('active');
            // PasPana fields
            $table->string('kode_perusahaan', 20)->nullable()->unique();
            $table->text('kebijakan_cuti')->nullable();
            $table->text('kebijakan_lembur')->nullable();
            $table->text('kebijakan_overtime')->nullable();
            $table->string('tax_no', 50)->nullable();
            $table->text('alasan_resign')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->string('nomor_rekening', 30)->nullable();
            $table->string('npwp_resign', 20)->nullable();
            $table->string('telepon', 20)->nullable();
            $table->string('status_perusahaan', 50)->default('active');
            $table->string('inisial', 10)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
