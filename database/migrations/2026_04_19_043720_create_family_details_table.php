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
        Schema::create('family_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->string('name');
            $table->string('relationship', 50);
            $table->char('gender', 1)->nullable(); 
            $table->text('nik')->nullable(); 
            $table->date('birth_date')->nullable();
            $table->string('job')->nullable();
            $table->text('phone')->nullable();
            $table->text('address')->nullable(); 
            $table->boolean('is_emergency')->default(false); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('family_details');
    }
};
