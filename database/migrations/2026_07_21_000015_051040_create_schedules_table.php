<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->boolean('is_off')->default(false);
            $table->timestamps();
            $table->unique(['user_id', 'date']);
        });

        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->foreign('schedule_id')->references('id')->on('schedules')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->dropForeign(['schedule_id']);
        });

        Schema::dropIfExists('schedules');
    }
};
