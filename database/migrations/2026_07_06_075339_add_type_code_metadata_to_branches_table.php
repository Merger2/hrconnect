<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->after('name');
            $table->string('type')->default('office')->after('code');
            $table->json('metadata')->nullable()->after('radius');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {
            $table->dropColumn(['code', 'type', 'metadata']);
        });
    }
};
