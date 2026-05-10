<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('overtimes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('attendance_id')->nullable()->constrained('attendances')->nullOnDelete();
            $table->date('date');
            $table->time('start_time');
            $table->time('end_time');
            $table->text('description');
            $table->decimal('total_hours', 5, 2)->nullable();
            $table->decimal('amount', 15, 2)->nullable(); 
            $table->text('rejection_reason')->nullable();
            $table->string('status', 20)->default('pending');
            $table->index('employee_id');
            $table->index('status');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('overtimes');
    }
};
