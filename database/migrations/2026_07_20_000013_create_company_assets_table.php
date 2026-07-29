<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_assets', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kode_aset', 50)->unique()->nullable();
            $table->string('category');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number', 100)->nullable()->unique();
            $table->decimal('purchase_price', 15, 2)->nullable();
            $table->date('purchase_date')->nullable();
            $table->enum('status', ['available', 'assigned', 'maintenance', 'retired'])->default('available');
            $table->foreignId('assigned_to')->nullable()->constrained('employees');
            $table->date('assigned_at')->nullable();
            $table->text('specifications')->nullable();
            $table->string('photo_path', 2048)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_assets');
    }
};
