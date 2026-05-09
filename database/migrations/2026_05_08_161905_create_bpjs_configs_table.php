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
        Schema::create('bpjs_configs', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('employer_rate', 5, 4);
            $table->decimal('employee_rate', 5, 4);
            $table->decimal('ceiling', 15, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bpjs_configs');
    }
};
