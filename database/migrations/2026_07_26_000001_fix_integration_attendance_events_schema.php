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

        if (Schema::hasColumn('integration_attendance_events', 'client_id')) {
            Schema::table('integration_attendance_events', function (Blueprint $table) {
                $table->dropForeign(['client_id']);
            });
            DB::statement('ALTER TABLE integration_attendance_events RENAME COLUMN client_id TO integration_client_id');
            DB::statement('ALTER TABLE integration_attendance_events ADD CONSTRAINT integration_attendance_events_integration_client_id_foreign FOREIGN KEY (integration_client_id) REFERENCES integration_clients (id)');
        }

        if (Schema::hasColumn('integration_attendance_events', 'employee_external_id')) {
            DB::statement('ALTER TABLE integration_attendance_events RENAME COLUMN employee_external_id TO employee_code');
        }

        if (Schema::hasColumn('integration_attendance_events', 'event_time')) {
            DB::statement('ALTER TABLE integration_attendance_events RENAME COLUMN event_time TO occurred_at');
        }

        if (! Schema::hasColumn('integration_attendance_events', 'source')) {
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
    }

    public function down(): void
    {
        // No down — handled by 2026_07_20_000107
    }
};
