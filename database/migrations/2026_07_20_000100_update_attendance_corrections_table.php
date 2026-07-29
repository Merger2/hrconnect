<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('attendance_id')->nullable()->constrained('attendances');
            $table->string('request_type', 50)->nullable();
            $table->timestamp('requested_time_in')->nullable();
            $table->timestamp('requested_time_out')->nullable();
            $table->foreignId('requested_shift_id')->nullable()->constrained('shifts');
            $table->jsonb('current_snapshot')->nullable();
            $table->foreignId('head_approved_by')->nullable()->constrained('users');
            $table->timestamp('head_approved_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users');
            $table->timestamp('reviewed_at')->nullable();
            $table->text('rejection_note')->nullable();

            $table->dropForeign(['approved_by']);
        });

        DB::statement('ALTER TABLE attendance_corrections ADD CONSTRAINT attendance_corrections_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES users (id)');

        DB::statement('ALTER TABLE attendance_corrections ALTER COLUMN status TYPE varchar(20) USING status::varchar(20)');
        DB::statement("ALTER TABLE attendance_corrections ALTER COLUMN status SET DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['attendance_id']);
            $table->dropForeign(['requested_shift_id']);
            $table->dropForeign(['head_approved_by']);
            $table->dropForeign(['reviewed_by']);
        });

        DB::statement('ALTER TABLE attendance_corrections DROP CONSTRAINT IF EXISTS attendance_corrections_approved_by_foreign');
        DB::statement('ALTER TABLE attendance_corrections ADD CONSTRAINT attendance_corrections_approved_by_foreign FOREIGN KEY (approved_by) REFERENCES employees (id)');

        Schema::table('attendance_corrections', function (Blueprint $table) {
            $table->dropColumn([
                'user_id',
                'attendance_id',
                'request_type',
                'requested_time_in',
                'requested_time_out',
                'requested_shift_id',
                'current_snapshot',
                'head_approved_by',
                'head_approved_at',
                'reviewed_by',
                'reviewed_at',
                'rejection_note',
            ]);
        });
    }
};
