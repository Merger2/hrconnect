<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $fks = ['employees_province_id_foreign', 'employees_city_id_foreign', 'employees_district_id_foreign', 'employees_village_id_foreign'];

        foreach ($fks as $fk) {
            DB::statement("ALTER TABLE employees DROP CONSTRAINT IF EXISTS {$fk}");
        }
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            if (Schema::hasTable('provinces')) {
                $table->foreign('province_id')->references('id')->on('provinces')->restrictOnDelete();
                $table->foreign('city_id')->references('id')->on('cities')->restrictOnDelete();
                $table->foreign('district_id')->references('id')->on('districts')->restrictOnDelete();
                $table->foreign('village_id')->references('id')->on('villages')->restrictOnDelete();
            }
        });
    }
};
