<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('approval_matrix_rules', function (Blueprint $table) {
            $table->id();
            $table->string('module_name');
            $table->string('condition_type');
            $table->string('condition_value');
            $table->integer('approval_level');
            $table->foreignId('approver_id')->nullable()->constrained('employees');
            $table->foreignId('approver_role_id')->nullable()->constrained('roles');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('approval_matrix_rules');
    }
};
