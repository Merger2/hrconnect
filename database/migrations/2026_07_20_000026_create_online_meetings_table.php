<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('online_meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('organizer_id')->constrained('employees');
            $table->timestamp('start_time');
            $table->timestamp('end_time');
            $table->string('meeting_url', 2048);
            $table->string('platform')->default('google_meet');
            $table->string('meeting_id', 100)->nullable();
            $table->string('password', 100)->nullable();
            $table->enum('status', ['scheduled', 'ongoing', 'completed', 'cancelled'])->default('scheduled');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('meeting_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('online_meeting_id')->constrained('online_meetings')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->boolean('is_required')->default(true);
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();
            $table->timestamps();
            $table->unique(['online_meeting_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_participants');
        Schema::dropIfExists('online_meetings');
    }
};
