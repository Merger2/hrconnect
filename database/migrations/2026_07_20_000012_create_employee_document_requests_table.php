<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_document_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained('employee_document_types');
            $table->foreignId('requested_by')->nullable()->constrained('users');
            $table->string('request_source', 20);
            $table->text('purpose');
            $table->text('details')->nullable();
            $table->date('due_date')->nullable();
            $table->enum('status', [
                'pending',
                'requested',
                'upload_processing',
                'uploaded',
                'generated',
                'ready',
                'rejected',
                'expired',
            ])->default('pending');
            $table->string('uploaded_path', 2048)->nullable();
            $table->string('uploaded_original_name', 255)->nullable();
            $table->timestamp('uploaded_at')->nullable();
            $table->string('generated_path', 2048)->nullable();
            $table->foreignId('generated_template_id')->nullable()->constrained('employee_document_templates');
            $table->timestamp('generated_at')->nullable();
            $table->json('metadata')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('fulfillment_note')->nullable();
            $table->text('rejection_note')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_document_requests');
    }
};
