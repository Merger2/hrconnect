<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Audit trail untuk kalkulasi payroll.
     *
     * Disimpan saat HitungGajiService dijalankan (status draft).
     * Setelah payroll PUBLISHED/PAID, baris ini immutable (tidak bisa diubah).
     * Tujuannya: traceability — kalau ada komplain "gaji salah", kita bisa
     * buka persis komponen apa yang masuk ke net_salary.
     */
    public function up(): void
    {
        Schema::create('payroll_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payroll_id')->constrained('payrolls')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();

            // Snapshot komponen perhitungan (semua dalam decimal 15,2)
            $table->decimal('basic_salary', 15, 2);
            $table->decimal('total_allowance', 15, 2);
            $table->decimal('overtime_pay', 15, 2);
            $table->decimal('gross_salary', 15, 2);

            // PPh21 breakdown
            $table->decimal('ptkp', 15, 2)->default(0);
            $table->decimal('pph21_ter', 15, 2)->default(0);
            $table->decimal('pph21_netto', 15, 2)->default(0);
            $table->decimal('pph21_rate', 5, 2)->default(0);

            // BPJS
            $table->decimal('bpjs_health', 15, 2)->default(0);
            $table->decimal('bpjs_employment', 15, 2)->default(0);

            // Potongan
            $table->decimal('loan_deduction', 15, 2)->default(0);
            $table->decimal('attendance_penalty', 15, 2)->default(0);
            $table->decimal('total_deduction', 15, 2);
            $table->decimal('net_salary', 15, 2);

            // Metadata kalkulasi
            $table->json('meta')->nullable()->comment('Raw input dari sub-service (TunjanganService, LemburService, dll)');
            $table->string('calculated_by')->nullable()->comment('User ID yang trigger kalkulasi');
            $table->timestamp('calculated_at')->useCurrent();

            $table->unique(['payroll_id']);
            $table->index(['employee_id', 'created_at']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_audits');
    }
};
