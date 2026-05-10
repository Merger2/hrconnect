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
        Schema::create('reimbursements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('payroll_id')->nullable()->constrained('payrolls')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('reimbursement_categories')->nullOnDelete();
            $table->string('title');
            $table->date('expense_date');
            $table->decimal('amount',15,2);
            $table->text('description')->nullable();
            $table->string('receipt_file')->nullable();
            $table->string('attachment_path', 255)->nullable();
            $table->text('rejection_reason')->nullable();
            $table->string('status',20)->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     * 
     */
    public function down(): void
    {
        Schema::dropIfExists('reimbursements');
    }
};
