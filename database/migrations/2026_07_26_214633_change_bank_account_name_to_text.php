<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE employees ALTER COLUMN bank_account_name TYPE text');
            DB::statement('ALTER TABLE employees ALTER COLUMN bank_account_holder TYPE text');
        } else {
            Schema::table('employees', function ($table) {
                $table->text('bank_account_name')->nullable()->change();
                $table->text('bank_account_holder')->nullable()->change();
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE employees ALTER COLUMN bank_account_name TYPE varchar(100)');
            DB::statement('ALTER TABLE employees ALTER COLUMN bank_account_holder TYPE varchar(100)');
        } else {
            Schema::table('employees', function ($table) {
                $table->string('bank_account_name', 100)->nullable()->change();
                $table->string('bank_account_holder', 100)->nullable()->change();
            });
        }
    }
};
