<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hr_checklist_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('case_id')->constrained('hr_checklist_cases')->cascadeOnDelete();
            $table->foreignId('template_item_id')->nullable()->constrained('hr_checklist_template_items')->nullOnDelete();
            $table->unsignedBigInteger('depends_on_task_id')->nullable();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('category')->nullable();
            $table->date('due_date')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_original_name')->nullable();
            $table->timestamp('attachment_uploaded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hr_checklist_tasks');
    }
};
