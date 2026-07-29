<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_opportunities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->string('name');
            $table->string('stage')->default('lead'); // lead, qualified, proposal, negotiation, closed_won, closed_lost
            $table->decimal('expected_value', 15, 2)->default(0);
            $table->decimal('probability', 5, 2)->default(0); // 0 - 100
            $table->date('expected_close_at')->nullable();
            $table->date('follow_up_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_opportunities');
    }
};
