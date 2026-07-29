<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_attendance_events', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('client_id')->constrained('integration_clients')->cascadeOnDelete();
            $table->string('event_type'); // clock_in, clock_out, etc
            $table->string('employee_external_id');
            $table->timestamp('event_time');
            $table->json('payload')->nullable();
            $table->string('status', 20)->default('pending'); // pending, processed, failed
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_attendance_events');
    }
};
