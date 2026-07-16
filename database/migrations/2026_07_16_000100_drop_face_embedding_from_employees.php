<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Face descriptors now live exclusively in the `face_descriptors` table.
     * This migration removes the legacy `employees.face_embedding` column
     * and the now-redundant index to keep a single source of truth.
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasColumn('employees', 'face_embedding')) {
                $table->dropColumn('face_embedding');
            }
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (! Schema::hasColumn('employees', 'face_embedding')) {
                // Re-add as text; pgvector recreation is out of scope for rollback.
                $table->text('face_embedding')->nullable()->comment('Legacy fallback removed; see face_descriptors table');
            }
        });
    }
};
