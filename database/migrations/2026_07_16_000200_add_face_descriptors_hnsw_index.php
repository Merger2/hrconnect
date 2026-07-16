<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add an HNSW index on the embedding column for fast cosine similarity search.
     * PRD §2.1 requires vector index for production-scale face matching.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Ensure pgvector extension exists
            DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

            // HNSW index with cosine distance operator class
            DB::statement(
                'CREATE INDEX IF NOT EXISTS face_descriptors_embedding_hnsw_idx '
                .'ON face_descriptors USING hnsw (embedding vector_cosine_ops)'
            );

            // Composite index for active lookups per employee
            DB::statement(
                'CREATE INDEX IF NOT EXISTS face_descriptors_employee_active_idx '
                .'ON face_descriptors (employee_id, is_active)'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS face_descriptors_embedding_hnsw_idx');
            DB::statement('DROP INDEX IF EXISTS face_descriptors_employee_active_idx');
        }
    }
};
