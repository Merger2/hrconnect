<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_assets', function (Blueprint $table) {
            $table->date('expiration_date')->nullable();
            $table->date('return_date')->nullable();
            $table->text('notes')->nullable();
            $table->dropColumn(['kode_aset', 'brand', 'model', 'specifications', 'photo_path']);
        });

        DB::statement('ALTER TABLE company_assets RENAME COLUMN category TO type');
        DB::statement('ALTER TABLE company_assets RENAME COLUMN purchase_price TO purchase_cost');
        DB::statement('ALTER TABLE company_assets RENAME COLUMN assigned_at TO date_assigned');
        DB::statement('ALTER TABLE company_assets ALTER COLUMN status TYPE varchar(20) USING status::varchar(20)');
        DB::statement("ALTER TABLE company_assets ALTER COLUMN status SET DEFAULT 'available'");

        Schema::table('company_assets', function (Blueprint $table) {
            $table->dropForeign(['assigned_to']);
        });
        DB::statement('ALTER TABLE company_assets RENAME COLUMN assigned_to TO user_id');
        Schema::table('company_assets', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change()->constrained('users');
        });
    }

    public function down(): void
    {
        Schema::table('company_assets', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });
        DB::statement('ALTER TABLE company_assets RENAME COLUMN user_id TO assigned_to');
        Schema::table('company_assets', function (Blueprint $table) {
            $table->foreignId('assigned_to')->nullable()->constrained('employees');
            $table->string('kode_aset', 50)->unique()->nullable();
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->text('specifications')->nullable();
            $table->string('photo_path', 2048)->nullable();
        });

        DB::statement('ALTER TABLE company_assets RENAME COLUMN date_assigned TO assigned_at');
        DB::statement('ALTER TABLE company_assets RENAME COLUMN purchase_cost TO purchase_price');
        DB::statement('ALTER TABLE company_assets RENAME COLUMN type TO category');

        Schema::table('company_assets', function (Blueprint $table) {
            $table->dropColumn(['expiration_date', 'return_date', 'notes']);
        });
    }
};
