<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('employees')->nullOnDelete();
            $table->foreignId('company_id')->constrained('companies')->restrictOnDelete();
            $table->foreignId('branch_id')->constrained('branches')->restrictOnDelete();
            $table->foreignId('department_id')->constrained('departments')->restrictOnDelete();
            $table->foreignId('position_id')->constrained('positions')->restrictOnDelete();
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->restrictOnDelete();
            // --- ALAMAT(LARAVOLT) ---
            $table->foreignId('province_id')->nullable()->constrained('indonesia_provinces')->restrictOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('indonesia_cities')->restrictOnDelete();
            $table->foreignId('district_id')->nullable()->constrained('indonesia_districts')->restrictOnDelete();
            $table->foreignId('village_id')->nullable()->constrained('indonesia_villages')->restrictOnDelete();
            $table->string('postal_code', 10)->nullable();
            $table->text('address_detail')->nullable();
            // --- DATA PRIBADI ---
            $table->string('employee_number', 50)->unique();
            $table->string('full_name');
            $table->text('phone');
            $table->text('bank_account_number')->nullable();
            $table->string('bank_name', 100)->nullable();
            $table->text('npwp')->nullable();
            $table->text('nik');
            $table->string('marital_status', 20)->default('single');
            $table->string('blood_type', 5)->nullable();
            $table->char('gender', 1);
            $table->string('status', 20)->default('active');
            $table->date('birth_date');
            $table->date('join_date');
            $table->string('employment_type', 20)->default('permanent');
            $table->date('contract_start_date')->nullable();
            $table->date('contract_end_date')->nullable();
            $table->date('resign_date')->nullable();
            $table->date('deceased_date')->nullable();
            $table->string('termination_type', 20)->nullable();
            $table->text('termination_reason')->nullable();
            $table->string('pin', 60)->nullable()->comment('bcrypt hash, 6 digit PIN absensi');
            // --- BIOMETRIK & PENDIDIKAN ---
            $table->string('photo')->nullable();
            if (DB::getDriverName() === 'pgsql') {
                $table->vector('face_embedding', dimensions: 128)->nullable()->comment('Menyimpan vektor embedding wajah untuk keperluan absensi berbasis wajah');
            } else {
                $table->text('face_embedding')->nullable()->comment('Fallback: Menyimpan vektor embedding wajah (text pada SQLite)');
            }
            $table->string('education_level', 20);
            $table->string('institution_name');
            $table->string('major')->nullable();
            $table->integer('graduation_year');
            $table->string('salary_type', 20);
            // --- INDEXING (OPTIMASI PERFORMA) ---
            $table->index('full_name');
            $table->index('status');
            $table->index('join_date');
            $table->index('province_id');
            $table->index('city_id');
            // --- AUDIT TRAIL ---
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
