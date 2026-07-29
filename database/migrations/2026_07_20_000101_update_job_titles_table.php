<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE job_titles RENAME COLUMN title TO name');

        Schema::table('job_titles', function (Blueprint $table) {
            $table->foreignId('job_level_id')->nullable();
            $table->foreignId('division_id')->nullable()->constrained('divisions');
        });
    }

    public function down(): void
    {
        Schema::table('job_titles', function (Blueprint $table) {
            $table->dropForeign(['division_id']);
            $table->dropColumn(['job_level_id', 'division_id']);
        });

        DB::statement('ALTER TABLE job_titles RENAME COLUMN name TO title');
    }
};
