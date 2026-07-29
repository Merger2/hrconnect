<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_components', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique();
            $table->enum('type', ['allowance', 'deduction']);
            $table->text('description')->nullable();
            $table->boolean('is_taxable')->default(true);
            $table->boolean('is_bpjs_applicable')->default(true);
            $table->boolean('is_active')->default(true);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->timestamps();
        });

        DB::table('payroll_components')->insert([
            ['name' => 'Gaji Pokok', 'code' => 'gaji_pokok', 'type' => 'allowance', 'is_taxable' => true, 'is_bpjs_applicable' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tunjangan Transport', 'code' => 'tunj_transport', 'type' => 'allowance', 'is_taxable' => true, 'is_bpjs_applicable' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tunjangan Makan', 'code' => 'tunj_makan', 'type' => 'allowance', 'is_taxable' => false, 'is_bpjs_applicable' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Tunjangan Kesehatan', 'code' => 'tunj_kesehatan', 'type' => 'allowance', 'is_taxable' => false, 'is_bpjs_applicable' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Uang Lembur', 'code' => 'uang_lembur', 'type' => 'allowance', 'is_taxable' => true, 'is_bpjs_applicable' => true, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'THR', 'code' => 'thr', 'type' => 'allowance', 'is_taxable' => true, 'is_bpjs_applicable' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Potongan PPh21', 'code' => 'pph21', 'type' => 'deduction', 'is_taxable' => false, 'is_bpjs_applicable' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'BPJS Kesehatan', 'code' => 'bpjs_kesehatan', 'type' => 'deduction', 'is_taxable' => false, 'is_bpjs_applicable' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'BPJS Ketenagakerjaan', 'code' => 'bpjs_tk', 'type' => 'deduction', 'is_taxable' => false, 'is_bpjs_applicable' => false, 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Potongan Alpha', 'code' => 'alpha', 'type' => 'deduction', 'is_taxable' => false, 'is_bpjs_applicable' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_components');
    }
};
