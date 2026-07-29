<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kpi_template_id')->nullable()->constrained('kpi_templates')->cascadeOnDelete();
            $table->string('name');
            $table->decimal('weight', 5, 2);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_groups');
    }
};
