<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->dropColumn([
                'allowances_total',
                'deductions_total',
                'overtime_amount',
                'thp',
                'payroll_period',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('payrolls', function (Blueprint $table) {
            $table->decimal('allowances_total', 15, 2)->default(0)->after('total_allowance');
            $table->decimal('deductions_total', 15, 2)->default(0)->after('total_deduction');
            $table->decimal('overtime_amount', 15, 2)->default(0)->after('overtime_pay');
            $table->decimal('thp', 15, 2)->nullable()->after('net_salary');
            $table->string('payroll_period', 7)->nullable()->after('period');
        });
    }
};
