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
            $table->foreignId('division_id')->nullable()->constrained('divisions')->restrictOnDelete();
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
            $table->text('npwp')->nullable();  // Changed from string(15) to text for CipherSweet encryption
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
            $table->string('phk_variant', 20)->nullable();
            $table->string('pin', 60)->nullable()->comment('bcrypt hash, 6 digit PIN absensi');
            // --- QUANTA PAYROLL FIELDS ---
            $table->foreignId('golongan_ptkp_id')->nullable()->constrained('golongan_ptkp');
            $table->foreignId('tarif_ter_id')->nullable()->constrained('tarif_ter');
            $table->foreignId('kategori_ter_id')->nullable()->constrained('kategori_ter');
            $table->string('kode_karyawan', 20)->unique()->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->enum('status_karyawan', ['active', 'inactive', 'terminated'])->default('active');
            $table->string('nip', 20)->nullable()->unique()->comment('Nomor Induk Pegawai untuk laporan pajak');
            $table->string('bank_account_name', 100)->nullable()->comment('Nama sesuai rekening bank');
            $table->string('ptkp_status', 10)->nullable()->comment('Status PTKP: TK/0, K/0, K/1, K/2, K/3');
            $table->string('payslip_password', 100)->nullable();
            $table->timestamp('payslip_password_set_at')->nullable();
            $table->decimal('basic_salary', 15, 2)->nullable()->comment('Basic salary for payroll calc');
            $table->string('bank_account_holder', 100)->nullable();
            $table->text('emergency_contact_name')->nullable();
            $table->text('emergency_contact_phone')->nullable();
            $table->text('emergency_contact_relation')->nullable();
            $table->text('bpjs_kesehatan')->nullable();
            $table->text('bpjs_ketenagakerjaan')->nullable();
            // --- BIOMETRIK & PENDIDIKAN ---
            $table->string('photo')->nullable();
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

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE employees ADD CONSTRAINT employees_parent_not_self_check CHECK (parent_id IS NULL OR parent_id <> id)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
