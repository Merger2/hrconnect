<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            // Employment lifecycle
            $table->date('probation_ends_at')->nullable()->after('status');
            $table->date('contract_ends_at')->nullable()->after('probation_ends_at');
            $table->timestamp('resignation_submitted_at')->nullable()->after('contract_ends_at');
            $table->timestamp('resigned_at')->nullable()->after('resignation_submitted_at');
            $table->text('resignation_reason')->nullable()->after('resigned_at');
            $table->timestamp('exit_interview_completed_at')->nullable()->after('resignation_reason');
            $table->timestamp('account_auto_disable_at')->nullable()->after('exit_interview_completed_at');

            // Account lifecycle
            $table->string('employment_status')->default('active')->after('status');
            $table->timestamp('account_deletion_requested_at')->nullable()->after('employment_status');
            $table->text('account_deletion_reason')->nullable()->after('account_deletion_requested_at');
            $table->timestamp('account_deletion_reviewed_at')->nullable()->after('account_deletion_reason');
            $table->foreignId('account_deletion_reviewed_by')->nullable()->after('account_deletion_reviewed_at')->constrained('users')->nullOnDelete();
            $table->text('account_deletion_review_notes')->nullable()->after('account_deletion_reviewed_by');

            // Manager/Direct report
            $table->foreignId('manager_id')->nullable()->after('parent_id')->constrained('employees')->nullOnDelete();

            // Indexes
            $table->index('probation_ends_at');
            $table->index('contract_ends_at');
            $table->index('account_auto_disable_at');
            $table->index(['employment_status', 'status']);
            $table->index('account_deletion_requested_at');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropConstrainedForeignId('account_deletion_reviewed_by');
            $table->dropConstrainedForeignId('manager_id');
            $table->dropIndex(['probation_ends_at']);
            $table->dropIndex(['contract_ends_at']);
            $table->dropIndex(['account_auto_disable_at']);
            $table->dropIndex(['employment_status', 'status']);
            $table->dropIndex(['account_deletion_requested_at']);
            $table->dropColumn([
                'probation_ends_at',
                'contract_ends_at',
                'resignation_submitted_at',
                'resigned_at',
                'resignation_reason',
                'exit_interview_completed_at',
                'account_auto_disable_at',
                'employment_status',
                'account_deletion_requested_at',
                'account_deletion_reason',
                'account_deletion_reviewed_at',
                'account_deletion_reviewed_by',
                'account_deletion_review_notes',
                'manager_id',
            ]);
        });
    }
};
