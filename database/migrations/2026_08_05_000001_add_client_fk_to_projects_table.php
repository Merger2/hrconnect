<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * M13 AUDIT: projects.client_id dibuat nullable tanpa FK (temuan audit
     * 2026-08-04). Tambahkan FK ke `clients` (tabel dibuat 2026-07-28 oleh
     * OperationalWorkspace). Nilai client_id yang tidak ada di `clients`
     * di-null-kan dulu supaya ALTER TABLE tidak gagal.
     */
    public function up(): void
    {
        DB::table('projects')
            ->whereNotNull('client_id')
            ->whereNotIn('client_id', DB::table('clients')->select('id'))
            ->update(['client_id' => null]);

        Schema::table('projects', function (Blueprint $table) {
            $table->foreign('client_id')
                ->references('id')
                ->on('clients')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });
    }
};
