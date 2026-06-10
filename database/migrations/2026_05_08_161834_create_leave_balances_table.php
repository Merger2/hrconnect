<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->restrictOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->restrictOnDelete();
            $table->integer('year');
            $table->decimal('quota', 4, 1)->default(0);
            $table->decimal('used', 4, 1)->default(0);
            $table->decimal('carry_forward', 4, 1)->default(0);
            $table->date('carry_forward_deadline')->nullable();
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year']);

            if (DB::getDriverName() === 'pgsql') {
                DB::statement('ALTER TABLE leave_balances ADD CONSTRAINT leave_balances_used_check CHECK (used <= quota + carry_forward)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
