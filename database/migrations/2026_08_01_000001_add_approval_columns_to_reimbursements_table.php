<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->foreignId('approved_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->foreignId('head_approved_by')->nullable()->after('approved_by')->constrained('users')->nullOnDelete();
            $table->timestamp('head_approved_at')->nullable()->after('head_approved_by');
            $table->foreignId('finance_approved_by')->nullable()->after('head_approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('finance_approved_at')->nullable()->after('finance_approved_by');
            $table->foreignId('approval_matrix_rule_id')->nullable()->after('finance_approved_at')->constrained()->nullOnDelete();
            $table->json('approval_steps')->nullable()->after('approval_matrix_rule_id');
            $table->string('approval_current_step')->nullable()->after('approval_steps');
            $table->json('approval_completed_steps')->nullable()->after('approval_current_step');
        });
    }

    public function down(): void
    {
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('head_approved_by');
            $table->dropConstrainedForeignId('finance_approved_by');
            $table->dropConstrainedForeignId('approval_matrix_rule_id');
            $table->dropColumn([
                'head_approved_at',
                'finance_approved_at',
                'approval_steps',
                'approval_current_step',
                'approval_completed_steps',
            ]);
        });
    }
};
