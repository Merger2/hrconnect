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
            $table->foreignId('province_id')->nullable()->constrained('indonesia_provinces')->restrictOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('indonesia_cities')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('indonesia_districts')->restrictOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('indonesia_villages')->restrictOnDelete();
            $table->string('postal_code', 10)->nullable();
            $table->text('address_detail');
            $table->string('name');
            $table->string('phone');
            $table->string('email');
            $table->string('website')->nullable();
            $table->text('npwp');
            $table->string('code')->unique();
            $table->text('logo')->nullable();
            $table->boolean('is_active')->default(true);
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
