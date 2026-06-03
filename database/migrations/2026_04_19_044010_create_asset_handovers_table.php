<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_handovers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->index()->constrained('assets')->restrictOnDelete();
            $table->foreignId('employee_id')->index()->constrained('employees')->restrictOnDelete();
            $table->date('handover_date');
            $table->date('return_date')->nullable();
            $table->string('condition');
            $table->string('category', 30);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_handovers');
    }
};
