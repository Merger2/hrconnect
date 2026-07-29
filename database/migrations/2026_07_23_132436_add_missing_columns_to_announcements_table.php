<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('announcements', 'modal_behavior')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->string('modal_behavior')->default('acknowledge')->after('created_by');
            });
        }

        if (! Schema::hasColumn('announcements', 'is_active')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->boolean('is_active')->default(true)->after('modal_behavior');
            });
        }
    }

    public function down(): void
    {
        Schema::table('announcements', function (Blueprint $table) {
            $table->dropColumn(['modal_behavior', 'is_active']);
        });
    }
};
