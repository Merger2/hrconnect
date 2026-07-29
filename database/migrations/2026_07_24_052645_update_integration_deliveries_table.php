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
            $table->string('event_key')->nullable();
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('pending');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->longText('response_body')->nullable();
            $table->string('signature', 255)->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->timestamp('failed_at')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('integration_deliveries')) {
            return;
        }

        Schema::table('integration_deliveries', function (Blueprint $table) {
            $table->dropColumn([
                'event_key', 'payload', 'status', 'attempts',
                'response_status', 'response_body', 'signature',
                'dispatched_at', 'failed_at',
            ]);
        });
    }
};
