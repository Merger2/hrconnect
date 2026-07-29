<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // location_address is encrypted with CipherSweet — varchar(255) can overflow
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE work_from_home_requests ALTER COLUMN location_address TYPE text');
        } else {
            Schema::table('work_from_home_requests', function ($table) {
                $table->text('location_address')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE work_from_home_requests ALTER COLUMN location_address TYPE varchar(255)');
        } else {
            Schema::table('work_from_home_requests', function ($table) {
                $table->string('location_address', 255)->nullable()->change();
            });
        }
    }
};
