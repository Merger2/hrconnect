<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('integration_deliveries')) {
            return;
        }

        Schema::table('integration_deliveries', function (Blueprint $table) {
            $table->dropColumn(['payload_template', 'encryption_key', 'metadata']);
            $table->string('event_key');
            $table->json('payload')->nullable();
            $table->string('signature')->nullable();
            $table->unsignedTinyInteger('attempts')->default(1);
            $table->string('status', 20)->default('pending');
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->string('response_body', 2000)->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('failed_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('integration_deliveries', function (Blueprint $table) {
            $table->dropColumn([
                'event_key', 'payload', 'signature', 'attempts',
                'status', 'response_status', 'response_body',
                'dispatched_at', 'failed_at',
            ]);
            $table->text('payload_template')->nullable();
            $table->string('encryption_key')->nullable();
            $table->json('metadata')->nullable();
        });
    }
};
