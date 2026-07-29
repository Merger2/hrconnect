<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->foreignId('schedule_id')->nullable();
            $table->foreignId('current_shift_id')->nullable()->constrained('shifts');
            $table->foreignId('replacement_user_id')->nullable()->constrained('users');
            $table->text('reason')->nullable();
            $table->text('rejection_note')->nullable();
        });

        DB::statement('ALTER TABLE shift_swap_requests RENAME COLUMN requestor_id TO requester_id');
        DB::statement('ALTER TABLE shift_swap_requests RENAME COLUMN shift_requested_id TO requested_shift_id');
        DB::statement('ALTER TABLE shift_swap_requests RENAME COLUMN swap_date TO schedule_date');

        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->dropForeign(['shift_offered_id']);
        });
        DB::statement('ALTER TABLE shift_swap_requests DROP COLUMN shift_offered_id');

        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->dropForeign(['approved_by']);
        });
        DB::statement('ALTER TABLE shift_swap_requests RENAME COLUMN approved_by TO reviewed_by');
        DB::statement('ALTER TABLE shift_swap_requests RENAME COLUMN approved_at TO reviewed_at');

        DB::statement('ALTER TABLE shift_swap_requests ALTER COLUMN status TYPE varchar(20) USING status::varchar(20)');
        DB::statement("ALTER TABLE shift_swap_requests ALTER COLUMN status SET DEFAULT 'pending'");
    }

    public function down(): void
    {
        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['current_shift_id']);
            $table->dropForeign(['replacement_user_id']);
            $table->dropColumn([
                'user_id', 'schedule_id', 'current_shift_id',
                'replacement_user_id', 'reason', 'rejection_note',
            ]);
        });

        DB::statement('ALTER TABLE shift_swap_requests RENAME COLUMN reviewed_at TO approved_at');
        DB::statement('ALTER TABLE shift_swap_requests RENAME COLUMN reviewed_by TO approved_by');

        Schema::table('shift_swap_requests', function (Blueprint $table) {
            $table->foreignId('shift_offered_id')->constrained('shifts');
            $table->foreignId('approved_by')->nullable()->constrained('employees');
        });

        DB::statement('ALTER TABLE shift_swap_requests RENAME COLUMN schedule_date TO swap_date');
        DB::statement('ALTER TABLE shift_swap_requests RENAME COLUMN requested_shift_id TO shift_requested_id');
        DB::statement('ALTER TABLE shift_swap_requests RENAME COLUMN requester_id TO requestor_id');
    }
};
