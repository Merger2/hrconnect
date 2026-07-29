<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_checklist_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('hr_checklist_templates')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->string('default_assignee_type')->nullable();
            $table->integer('due_offset_days')->default(0);
            $table->boolean('is_required')->default(true);
            $table->integer('sort_order')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_checklist_template_items');
    }
};
