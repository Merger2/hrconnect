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
        Schema::create('leaves', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('day_type')->default('full_day');
            $table->decimal('total_days',4,2);
            $table->text('reason');
            $table->string('proof_file')->nullable();
            $table->string('status',20)->default('pending');
            $table->index('status',20);
            $table->index('start_date');
            $table->index(['employee_id', 'start_date']); // Penting untuk laporan harian seluruh kantor
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leaves');
    }
};
