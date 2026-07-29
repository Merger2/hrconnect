<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            if (! Schema::hasColumn('leave_types', 'category')) {
                $table->string('category')->default('other')->after('code');
            }
            if (! Schema::hasColumn('leave_types', 'description')) {
                $table->text('description')->nullable()->after('category');
            }
            if (! Schema::hasColumn('leave_types', 'counts_against_quota')) {
                $table->boolean('counts_against_quota')->default(true)->after('deducts_from_quota');
            }
            if (! Schema::hasColumn('leave_types', 'requires_attachment')) {
                $table->boolean('requires_attachment')->default(false)->after('counts_against_quota');
            }
            if (! Schema::hasColumn('leave_types', 'is_system')) {
                $table->boolean('is_system')->default(false)->after('is_active');
            }
            if (! Schema::hasColumn('leave_types', 'sort_order')) {
                $table->integer('sort_order')->default(50)->after('is_system');
            }
        });
    }

    public function down(): void
    {
        Schema::table('leave_types', function (Blueprint $table) {
            $columns = ['category', 'description', 'counts_against_quota', 'requires_attachment', 'is_system', 'sort_order'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('leave_types', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
