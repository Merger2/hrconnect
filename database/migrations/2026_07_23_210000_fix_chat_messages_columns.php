<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropForeign(['sender_id']);
            $table->dropColumn('sender_id');
            $table->foreignId('user_id')->constrained('users')->after('chat_thread_id');

            $table->renameColumn('message', 'body');

            $table->dropColumn('attachment_type');

            $table->string('attachment_disk')->nullable()->after('attachment_path');
            $table->string('attachment_name')->nullable()->after('attachment_disk');
            $table->string('attachment_mime')->nullable()->after('attachment_name');
            $table->unsignedBigInteger('attachment_size')->nullable()->after('attachment_mime');
            $table->json('metadata')->nullable()->after('attachment_size');
        });
    }

    public function down(): void
    {
        Schema::table('chat_messages', function (Blueprint $table) {
            $table->dropColumn([
                'user_id', 'attachment_disk', 'attachment_name',
                'attachment_mime', 'attachment_size', 'metadata',
            ]);

            $table->renameColumn('body', 'message');

            $table->foreignId('sender_id')->constrained('employees');
            $table->string('attachment_type', 50)->nullable();
        });
    }
};
