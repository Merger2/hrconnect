<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barcodes', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('value')->unique();
            $table->string('secret_key', 64)->nullable();
            $table->double('latitude', 10, 8)->default(0);
            $table->double('longitude', 11, 8)->default(0);
            $table->float('radius');
            $table->boolean('dynamic_enabled')->default(false);
            $table->unsignedSmallInteger('dynamic_ttl_seconds')->default(60);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barcodes');
    }
};
