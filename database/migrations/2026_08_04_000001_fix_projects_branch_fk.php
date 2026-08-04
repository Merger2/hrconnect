<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * projects.branch_id awalnya FK ke tabel legacy `branches`, padahal
     * OperationalWorkspace menulis id dari `company_branches` → FK violation.
     * Ubah constraint ke `company_branches` (sesuai pemakaian aktual).
     */
    public function up(): void
    {
        // Null-kan nilai branch_id yang tidak ada di company_branches
        // (sisa data yang menunjuk ke id tabel legacy `branches`) supaya
        // ALTER TABLE ADD CONSTRAINT tidak gagal.
        DB::table('projects')
            ->whereNotNull('branch_id')
            ->whereNotIn('branch_id', DB::table('company_branches')->select('id'))
            ->update(['branch_id' => null]);

        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')
                ->references('id')
                ->on('company_branches')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
        });
    }
};
