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
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            // Relasi
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->cascadeOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('positions')->cascadeOnDelete();
            // Data Pribadi
            $table->string('employee_number')->unique();
            $table->string('full_name');
            $table->string('nik')->unique();
            $table->string('gender');
            $table->string('employee_status')->default('active');
            $table->date('birth_date');
            $table->date('join_date');
            $table->date('resign_date')->nullable();
            $table->string('phone');
            $table->text('address');
            $table->string('photo')->nullable();
            // pendidikan
            $table->string('education_level');
            $table->string('institution_name');
            $table->string('major')->nullable();
            $table->integer('graduation_year');
            $table->string('salary_type');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
