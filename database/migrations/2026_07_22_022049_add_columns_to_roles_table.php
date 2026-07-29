<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->string('slug')->nullable()->after('name');
            $table->json('permission_keys')->nullable()->after('guard_name');
            $table->boolean('is_super_admin')->default(false)->after('permission_keys');
            $table->text('description')->nullable()->after('is_super_admin');
            $table->boolean('is_system')->default(false)->after('description');
        });

        DB::statement('UPDATE roles SET slug = LOWER(name) WHERE slug IS NULL');

        Schema::table('roles', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['slug', 'permission_keys', 'is_super_admin', 'description', 'is_system']);
        });
    }
};
