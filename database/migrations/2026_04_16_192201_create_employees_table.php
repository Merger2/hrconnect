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
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->cascadeOnDelete();
            $table->foreignId('position_id')->constrained('positions')->restrictOnDelete();
            // Data Pribadi
            $table->string('employee_number')->unique();
            $table->string('full_name');

            // [PENTING] Implementasi Keamanan NIK (Encrypted at Rest & Blind Index)
            $table->text('nik'); // Akan menyimpan string enkripsi yang panjang
            $table->string('nik_hash')->unique(); // Untuk pencarian & validasi unique

            $table->string('gender', 10); 
            $table->string('status', 20)->default('active');
            $table->date('birth_date');
            $table->date('join_date');
            $table->date('resign_date')->nullable();
            $table->string('phone');
            $table->text('address');
            $table->string('photo')->nullable();
            // pendidikan
            $table->string('education_level', 20);
            $table->string('institution_name');
            $table->string('major')->nullable();
            $table->integer('graduation_year');
            $table->string('salary_type', 20);
            $table->timestamps();
            $table->softDeletes();
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
