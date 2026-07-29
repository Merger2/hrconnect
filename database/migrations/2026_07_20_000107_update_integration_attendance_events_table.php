<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('integration_attendance_events')) {
            return;
        }

        Schema::table('integration_attendance_events', function (Blueprint $table) {
            $table->dropForeign(['client_id']);
        });

        DB::statement('ALTER TABLE integration_attendance_events RENAME COLUMN client_id TO integration_client_id');
        DB::statement('ALTER TABLE integration_attendance_events RENAME COLUMN employee_external_id TO employee_code');
        DB::statement('ALTER TABLE integration_attendance_events RENAME COLUMN event_time TO occurred_at');

        DB::statement('ALTER TABLE integration_attendance_events ADD CONSTRAINT integration_attendance_events_integration_client_id_foreign FOREIGN KEY (integration_client_id) REFERENCES integration_clients (id)');

        Schema::table('integration_attendance_events', function (Blueprint $table) {
            $table->dropColumn('payload');
            $table->string('source', 80)->nullable();
            $table->string('idempotency_key')->nullable()->index();
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('attendance_id')->nullable()->constrained('attendances');
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->string('device_id', 100)->nullable();
            $table->json('normalized_payload')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('processed_at')->nullable();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('integration_attendance_events')) {
            return;
        }

        Schema::table('integration_attendance_events', function (Blueprint $table) {
            $table->dropForeign(['integration_client_id']);
            $table->dropColumn([
                'source', 'idempotency_key', 'user_id', 'attendance_id',
                'latitude', 'longitude', 'device_id',
                'normalized_payload', 'raw_payload', 'processed_at',
            ]);
            $table->json('payload')->nullable();
        });

        DB::statement('ALTER TABLE integration_attendance_events RENAME COLUMN occurred_at TO event_time');
        DB::statement('ALTER TABLE integration_attendance_events RENAME COLUMN employee_code TO employee_external_id');
        DB::statement('ALTER TABLE integration_attendance_events RENAME COLUMN integration_client_id TO client_id');

        DB::statement('ALTER TABLE integration_attendance_events ADD CONSTRAINT integration_attendance_events_client_id_foreign FOREIGN KEY (client_id) REFERENCES integration_clients (id)');
    }
};
