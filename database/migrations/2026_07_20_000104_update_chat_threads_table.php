<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE chat_threads RENAME COLUMN subject TO title');

        Schema::table('chat_threads', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies');
            $table->boolean('is_archived')->default(false);
            $table->jsonb('metadata')->nullable();
            $table->dropForeign(['created_by']);
        });

        DB::statement('ALTER TABLE chat_threads ADD CONSTRAINT chat_threads_created_by_foreign FOREIGN KEY (created_by) REFERENCES users (id)');

        Schema::table('chat_thread_user', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropPrimary(['chat_thread_id', 'employee_id']);
        });
        DB::statement('ALTER TABLE chat_thread_user RENAME COLUMN employee_id TO user_id');
        Schema::table('chat_thread_user', function (Blueprint $table) {
            $table->foreignId('user_id')->change()->constrained('users');
            $table->primary(['chat_thread_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('chat_thread_user', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropPrimary(['chat_thread_id', 'user_id']);
        });
        DB::statement('ALTER TABLE chat_thread_user RENAME COLUMN user_id TO employee_id');
        Schema::table('chat_thread_user', function (Blueprint $table) {
            $table->foreignId('employee_id')->change()->constrained('employees');
            $table->primary(['chat_thread_id', 'employee_id']);
        });

        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
        });
        DB::statement('ALTER TABLE chat_threads ADD CONSTRAINT chat_threads_created_by_foreign FOREIGN KEY (created_by) REFERENCES employees (id)');

        Schema::table('chat_threads', function (Blueprint $table) {
            $table->dropColumn(['company_id', 'is_archived', 'metadata']);
        });

        DB::statement('ALTER TABLE chat_threads RENAME COLUMN title TO subject');
    }
};
