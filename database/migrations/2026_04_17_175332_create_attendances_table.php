<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('shift_id')->constrained('shifts')->restrictOnDelete();
            $table->date('date');
            $table->timestamp('clock_in');
            $table->timestamp('clock_out')->nullable();
            $table->decimal('lat_in', 10, 8)->nullable();
            $table->decimal('long_in', 11, 8)->nullable();
            $table->boolean('clock_in_is_mocked')->default(false);
            $table->decimal('clock_in_accuracy', 8, 2)->nullable();
            $table->decimal('lat_out', 10, 8)->nullable();
            $table->decimal('long_out', 11, 8)->nullable();
            $table->boolean('clock_out_is_mocked')->nullable();
            $table->decimal('clock_out_accuracy', 8, 2)->nullable();
            $table->string('device_fingerprint', 255)->nullable();
            $table->decimal('face_similarity_score', 5, 2)->nullable()->comment('Akurasi kemiripan wajah dalam persentase (%)');
            $table->string('photo_selfie_in')->nullable();
            $table->string('photo_selfie_out')->nullable();
            $table->string('status', 20)->default('on_time');
            $table->boolean('is_wfa')->default(false);
            $table->integer('late_minutes')->default(0);
            $table->unique(['employee_id', 'date']);
            $table->index(['date', 'status']);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
