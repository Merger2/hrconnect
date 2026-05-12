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
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->morphs('knowledgeable');
            $table->string('title');
            $table->text('content');
            $table->vector('embedding', dimensions: 1536);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });
        DB::statement('CREATE INDEX kb_embedding_hnsw_idx ON knowledge_bases USING hnsw (embedding vector_cosine_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};
