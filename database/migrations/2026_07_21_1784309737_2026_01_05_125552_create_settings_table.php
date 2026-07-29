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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general');
            $table->string('type')->default('string');
            $table->string('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->json('validation_rules')->nullable();
            $table->json('options')->nullable();
            $table->unsignedInteger('group_index')->default(0);
            $table->boolean('face_enrollment_required')->default(false);
            $table->boolean('face_verification_required')->default(true);
            $table->text('enterprise_license_key')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
