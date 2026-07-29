<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('late_tolerance_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            // Paspan fields
            $table->string('schedule_type', 20)->default('fixed');
            $table->string('shift_pattern', 50)->nullable();
            $table->integer('break_minutes')->default(60);
            $table->boolean('is_flexible')->default(false);
            $table->time('flexible_start')->nullable();
            $table->time('flexible_end')->nullable();
            // Soft deletes
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shifts');
    }
};
