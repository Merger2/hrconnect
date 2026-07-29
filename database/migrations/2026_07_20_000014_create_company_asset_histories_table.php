<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_asset_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_asset_id')->constrained('company_assets')->cascadeOnDelete();
            $table->enum('action', ['assigned', 'returned', 'maintenance', 'transferred']);
            $table->foreignId('from_employee_id')->nullable()->constrained('employees');
            $table->foreignId('to_employee_id')->nullable()->constrained('employees');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('employees');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_asset_histories');
    }
};
