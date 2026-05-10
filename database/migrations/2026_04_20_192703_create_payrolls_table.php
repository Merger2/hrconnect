<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payrolls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->string('period');
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('total_allowance', 15, 2);
            $table->decimal('gross_salary', 15, 2);
            $table->decimal('overtime_pay', 15, 2)->default(0);
            $table->decimal('pph21', 15, 2)->default(0);
            $table->decimal('bpjs_health', 15, 2)->default(0);
            $table->decimal('bpjs_employment', 15, 2)->default(0);
            $table->decimal('loan_deduction', 15, 2)->default(0);
            $table->decimal('attendance_penalty', 15, 2)->default(0);
            $table->decimal('total_deduction', 15, 2);
            $table->decimal('net_salary', 15, 2);
            $table->string('status', 20)->default('draft');
            $table->unique(['employee_id', 'period']);
            $table->index('status');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payrolls');
    }
};
