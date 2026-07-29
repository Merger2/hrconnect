<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_advances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('purpose')->nullable();
            $table->string('status')->default('pending');
            $table->foreignId('approval_matrix_rule_id')->nullable()->constrained()->nullOnDelete();
            $table->json('approval_steps')->nullable();
            $table->integer('approval_current_step')->default(0);
            $table->json('approval_completed_steps')->nullable();
            $table->tinyInteger('payment_month')->nullable();
            $table->integer('payment_year')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('head_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('head_approved_at')->nullable();
            $table->foreignId('finance_approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('finance_approved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_advances');
    }
};
