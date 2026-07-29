<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('job_levels', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('rank')->default(0);
            $table->timestamps();
        });

        Schema::table('job_titles', function (Blueprint $table) {
            $table->foreign('job_level_id')->references('id')->on('job_levels')->cascadeOnUpdate();
        });
    }

    public function down(): void
    {
        Schema::table('job_titles', function (Blueprint $table) {
            $table->dropForeign(['job_level_id']);
        });

        Schema::dropIfExists('job_levels');
    }
};
