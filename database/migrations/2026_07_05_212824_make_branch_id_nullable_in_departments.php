<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE departments ALTER COLUMN branch_id DROP NOT NULL');
        } else {
            Schema::table('departments', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE departments ALTER COLUMN branch_id SET NOT NULL');
        } else {
            Schema::table('departments', function (Blueprint $table) {
                $table->foreignId('branch_id')->nullable(false)->change();
            });
        }
    }
};
