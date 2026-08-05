<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Q1 (PHPStan) fix: SendPayrollPayslipEmail membaca & menulis `pdf_emailed_at`
     * padahal kolom tidak pernah dibuat → `forceFill(['pdf_emailed_at' => now()])`
     * → QueryException (column does not exist) setiap job payslip email jalan.
     * Tambahkan kolom sesuai intent kode (dedup email per payroll).
     */
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->timestamp('pdf_emailed_at')->nullable()->after('payment_method');
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn('pdf_emailed_at');
        });
    }
};
