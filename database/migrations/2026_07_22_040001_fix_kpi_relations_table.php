<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kpi_templates', function (Blueprint $table) {
            $table->foreignId('kpi_group_id')->nullable()->constrained('kpi_groups')->cascadeOnDelete()->after('id');
            $table->string('indicator_description')->nullable()->after('name');
            $table->decimal('weight', 5, 2)->default(0)->after('indicator_description');
        });

        Schema::table('kpi_groups', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('sort_order');
            $table->dropForeign(['kpi_template_id']);
            $table->dropColumn('kpi_template_id');
        });
    }

    public function down(): void
    {
        Schema::table('kpi_templates', function (Blueprint $table) {
            $table->dropColumn(['kpi_group_id', 'indicator_description', 'weight']);
        });

        Schema::table('kpi_groups', function (Blueprint $table) {
            $table->dropColumn('is_active');
            $table->foreignId('kpi_template_id')->nullable()->constrained('kpi_templates')->cascadeOnDelete();
        });
    }
};
