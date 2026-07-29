<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('work_from_home_requests', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->after('user_id');
            $table->date('date')->nullable()->after('end_date');
            $table->time('start_time')->nullable()->after('date');
            $table->time('end_time')->nullable()->after('start_time');
            $table->string('location_address')->nullable()->after('end_time');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->after('approved_by');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            $table->text('review_note')->nullable()->after('reviewed_at');
            $table->text('rejection_reason')->nullable()->after('review_note');
            $table->json('metadata')->nullable()->after('rejection_reason');
        });
    }

    public function down(): void
    {
        Schema::table('work_from_home_requests', function (Blueprint $table) {
            $table->dropColumn([
                'company_id', 'date', 'start_time', 'end_time', 'location_address',
                'reviewed_by', 'reviewed_at', 'review_note', 'rejection_reason', 'metadata',
            ]);
        });
    }
};
