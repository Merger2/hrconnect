<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('online_meetings', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->foreignId('chat_thread_id')->nullable()->constrained('chat_threads');
            $table->jsonb('metadata')->nullable();
            $table->dropForeign(['organizer_id']);
        });

        DB::statement('ALTER TABLE online_meetings RENAME COLUMN organizer_id TO host_id');
        DB::statement('ALTER TABLE online_meetings ADD CONSTRAINT online_meetings_host_id_foreign FOREIGN KEY (host_id) REFERENCES users (id)');

        DB::statement('ALTER TABLE online_meetings ALTER COLUMN status TYPE varchar(20) USING status::varchar(20)');
        DB::statement("ALTER TABLE online_meetings ALTER COLUMN status SET DEFAULT 'scheduled'");
    }

    public function down(): void
    {
        Schema::table('online_meetings', function (Blueprint $table) {
            $table->dropForeign(['host_id']);
        });
        DB::statement('ALTER TABLE online_meetings RENAME COLUMN host_id TO organizer_id');
        DB::statement('ALTER TABLE online_meetings ADD CONSTRAINT online_meetings_organizer_id_foreign FOREIGN KEY (organizer_id) REFERENCES employees (id)');

        Schema::table('online_meetings', function (Blueprint $table) {
            $table->dropColumn(['company_id', 'chat_thread_id', 'metadata']);
        });
    }
};
