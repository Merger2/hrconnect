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
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('position_id')->constrained('positions')->restrictOnDelete();
            // Data Pribadi
            $table->string('employee_number',50)->unique();
            $table->string('full_name');
            $table->string('phone');
            $table->string('phone_hash')->unique();
            $table->text('bank_account_number');
            $table->string('bank_account_number_hash')->unique();
            $table->string('bank_name',100);
            $table->text('npwp');
            $table->string('npwp_hash')->unique();
            $table->text('nik'); // Akan menyimpan string enkripsi yang panjang
            $table->string('nik_hash')->unique(); // Untuk pencarian & validasi unique
            $table->string('marital_status',20)->default('single');
            $table->string('blood_type',5)->nullable();
            $table->char('gender', 1); 
            $table->string('status', 20)->default('active');
            $table->date('birth_date');
            $table->date('join_date');
            $table->date('resign_date')->nullable();
            $table->text('address');
            $table->string('photo')->nullable();
            // pendidikan
            $table->string('education_level', 20);
            $table->string('institution_name');
            $table->string('major')->nullable();
            $table->year('graduation_year');
            $table->string('salary_type', 20);
            // indexing
            $table->index('full_name');
            $table->index('status');
            $table->index('join_date');
            // audit trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
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
