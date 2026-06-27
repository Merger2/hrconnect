<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->unsignedTinyInteger('risk_score')->nullable()->after('late_minutes');
            $table->string('risk_level', 10)->nullable()->after('risk_score');
            $table->json('risk_factors')->nullable()->after('risk_level');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['risk_score', 'risk_level', 'risk_factors']);
        });
    }
};
