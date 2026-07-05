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
            DB::statement('ALTER TABLE positions ALTER COLUMN department_id DROP NOT NULL');
            DB::statement('ALTER TABLE positions ALTER COLUMN grade DROP NOT NULL');
            DB::statement('ALTER TABLE positions ALTER COLUMN basic_salary DROP NOT NULL');
        } else {
            Schema::table('positions', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable()->change();
                $table->integer('grade')->nullable()->change();
                $table->decimal('basic_salary', 15, 2)->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE positions ALTER COLUMN department_id SET NOT NULL');
            DB::statement('ALTER TABLE positions ALTER COLUMN grade SET NOT NULL');
            DB::statement('ALTER TABLE positions ALTER COLUMN basic_salary SET NOT NULL');
        } else {
            Schema::table('positions', function (Blueprint $table) {
                $table->foreignId('department_id')->nullable(false)->change();
                $table->integer('grade')->nullable(false)->change();
                $table->decimal('basic_salary', 15, 2)->nullable(false)->change();
            });
        }
    }
};
