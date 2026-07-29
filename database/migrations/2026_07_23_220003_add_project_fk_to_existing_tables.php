<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('project_visit_evidences')) {
            Schema::table('project_visit_evidences', function (Blueprint $table) {
                $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_visit_evidences')) {
            Schema::table('project_visit_evidences', function (Blueprint $table) {
                $table->dropForeign(['project_id']);
            });
        }
    }
};
