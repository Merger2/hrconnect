<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_base_chunks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('knowledge_base_id')->constrained('knowledge_bases')->cascadeOnDelete();
            $table->text('chunk_text');
            $table->integer('chunk_index');
            $table->string('embedding_model', 100)->nullable();
            $table->integer('token_count')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_base_chunks');
    }
};
