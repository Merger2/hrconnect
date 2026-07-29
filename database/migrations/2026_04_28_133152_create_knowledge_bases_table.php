<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('knowledge_bases', function (Blueprint $table) {
            $table->id();
            $table->morphs('knowledgeable');
            $table->string('title');
            $table->text('content');
            $table->string('category', 30)->default('general');

            if (DB::getDriverName() === 'pgsql') {
                $table->vector('embedding', dimensions: 768)->nullable();
            } else {
                $table->text('embedding')->nullable();
            }

            $table->string('status', 20)->default('processing');
            $table->string('source_document')->nullable();
            $table->integer('page_number')->nullable();
            // RAG extended fields
            $table->foreignId('category_id')->nullable()->constrained('knowledge_base_categories');
            $table->text('summary')->nullable();
            $table->string('source_type', 50)->nullable();
            $table->string('file_type', 50)->nullable();
            $table->integer('file_size')->nullable();
            $table->string('original_filename', 255)->nullable();
            $table->integer('chunk_count')->default(0);
            $table->boolean('is_indexed')->default(false);
            $table->timestamp('indexed_at')->nullable();
            if (DB::getDriverName() === 'pgsql') {
                $table->jsonb('metadata')->nullable();
            } else {
                $table->json('metadata')->nullable();
            }

            $table->timestamps();
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX kb_embedding_hnsw_idx ON knowledge_bases USING hnsw (embedding vector_cosine_ops)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('knowledge_bases');
    }
};
