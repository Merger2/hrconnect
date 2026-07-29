<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_visit_evidences', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->string('event')->nullable();
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->json('attributes')->nullable();
            $table->json('old_attributes')->nullable();
            $table->json('tags')->nullable();
            $table->string('performed_by_type')->nullable();
            $table->unsignedBigInteger('performed_by_id')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('evidence_url')->nullable();
            $table->string('evidence_type')->nullable();
            $table->string('evidence_filename')->nullable();
            $table->unsignedBigInteger('evidence_size')->nullable();
            $table->json('evidence_metadata')->nullable();
            $table->string('evidence_hash')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->text('evidence_description')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_visit_evidences');
    }
};
