<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integration_deliveries', function (Blueprint $table) {
            // Drop old columns that don't match the current model
            if (Schema::hasColumn('integration_deliveries', 'payload_template')) {
                $table->dropColumn('payload_template');
            }
            if (Schema::hasColumn('integration_deliveries', 'encryption_key')) {
                $table->dropColumn('encryption_key');
            }
            if (Schema::hasColumn('integration_deliveries', 'metadata')) {
                $table->dropColumn('metadata');
            }

            // Add columns matching IntegrationDelivery model $fillable
            if (! Schema::hasColumn('integration_deliveries', 'event_key')) {
                $table->string('event_key')->after('integration_endpoint_id');
            }
            if (! Schema::hasColumn('integration_deliveries', 'payload')) {
                $table->json('payload')->nullable()->after('event_key');
            }
            if (! Schema::hasColumn('integration_deliveries', 'status')) {
                $table->string('status', 20)->default('pending')->after('payload');
            }
            if (! Schema::hasColumn('integration_deliveries', 'attempts')) {
                $table->unsignedTinyInteger('attempts')->default(0)->after('status');
            }
            if (! Schema::hasColumn('integration_deliveries', 'response_status')) {
                $table->unsignedSmallInteger('response_status')->nullable()->after('attempts');
            }
            if (! Schema::hasColumn('integration_deliveries', 'response_body')) {
                $table->text('response_body')->nullable()->after('response_status');
            }
            if (! Schema::hasColumn('integration_deliveries', 'signature')) {
                $table->string('signature', 255)->nullable()->after('response_body');
            }
            if (! Schema::hasColumn('integration_deliveries', 'dispatched_at')) {
                $table->timestamp('dispatched_at')->nullable()->after('signature');
            }
            if (! Schema::hasColumn('integration_deliveries', 'failed_at')) {
                $table->timestamp('failed_at')->nullable()->after('dispatched_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('integration_deliveries', function (Blueprint $table) {
            $table->dropColumn([
                'event_key', 'payload', 'status', 'attempts',
                'response_status', 'response_body', 'signature',
                'dispatched_at', 'failed_at',
            ]);
            $table->text('payload_template')->nullable();
            $table->string('encryption_key')->nullable();
            $table->json('metadata')->nullable();
        });
    }
};
