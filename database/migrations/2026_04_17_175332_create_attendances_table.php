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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();
            $table->date('date');
            $table->timestamp('clock_in');
            $table->timestamp('clock_out')->nullable();
            $table->decimal('lat_in',10,8)->nullable();
            $table->decimal('long_in',11,8)->nullable();
            $table->decimal('lat_out',10,8)->nullable();
            $table->decimal('long_out',11,8)->nullable();
            $table->decimal('face_similarity_score', 5, 2)->nullable()->comment('Akurasi kemiripan wajah dalam persentase (%)');
            $table->string('status', 20)->default('on_time');
            $table->boolean('is_wfa')->default(false);
            $table->string('photo_selfie_in')->nullable();
            $table->string('photo_selfie_out')->nullable();
            $table->unique(['employee_id','date']);
            $table->index(['date','status']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
