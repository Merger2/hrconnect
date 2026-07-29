<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * employees.pin originally varchar(60) for bcrypt (60 chars).
     * Argon2id hashes are 96-128 chars, so widen to varchar(255).
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('pin', 255)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * ⚠️ WARNING: Reverting to varchar(60) will TRUNCATE any existing
     * argon2id hashes (>96 chars). Only roll back if you have verified
     * that no argon2id hashes exist in the pin column, or re-hash all
     * pins with bcrypt before rolling back.
     */
    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->string('pin', 60)->change();
        });
    }
};
