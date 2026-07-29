<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activity_log_details')) {
            return;
        }

        Schema::create('activity_log_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('activity_log_id')->constrained('activity_logs')->cascadeOnDelete();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->string('field', 50);
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();
            $table->json('metadata')->nullable();
            $table->string('integrity_hash', 64);
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_log_details');
    }
};
