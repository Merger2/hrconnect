<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * employees.position_id awalnya NOT NULL, tapi UserForm.rule `position_id`
     * nullable (hanya required untuk group tertentu) → insert tanpa position
     * kena QueryException tak tertangani. PayrollCalculatorService punya guard
     * eksplisit (BusinessRuleException) kalau employee belum punya position,
     * jadi employee tanpa position memang didukung secara desain.
     * Buat kolom nullable (FK tetap dipertahankan; NULL lolos FK).
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->foreignId('position_id')->nullable()->change()->constrained('positions')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['position_id']);
            $table->foreignId('position_id')->nullable(false)->change()->constrained('positions')->restrictOnDelete();
        });
    }
};
