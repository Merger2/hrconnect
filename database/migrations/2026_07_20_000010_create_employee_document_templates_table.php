<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_document_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_type_id')->constrained('employee_document_types')->cascadeOnDelete();
            $table->string('name');
            $table->text('content')->nullable();
            $table->text('variables')->nullable();
            $table->string('paper_size', 20)->nullable();
            $table->string('orientation', 20)->nullable();
            $table->text('header')->nullable();
            $table->text('footer')->nullable();
            $table->json('layout_options')->nullable();
            $table->string('file_path', 2048)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_document_templates');
    }
};
