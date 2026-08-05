<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Q1 (PHPStan) fix: PayrollSettings CRUD menulis `amount` + `calculation_type`
     * padahal kolom tidak ada di payroll_components → Eloquent mass-assignment
     * (fillable) MEMBUANG keduanya secara diam-diam (silent data loss — UI
     * simpan komponen, nilai amount hilang, edit selalu kosong).
     * Tambahkan kolom sesuai intent UI (fixed / daily_presence / percentage_basic).
     */
    public function up(): void
    {
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->decimal('amount', 15, 2)->nullable()->after('type');
            $table->string('calculation_type', 30)->default('fixed')->after('amount');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_components', function (Blueprint $table) {
            $table->dropColumn(['amount', 'calculation_type']);
        });
    }
};
