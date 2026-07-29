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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            // Foreign Key ke tabel departements
            $table->foreignId('division_id')->nullable()->constrained('divisions')->restrictOnDelete();
            $table->string('name');
            $table->string('code')->unique();
            $table->integer('grade')->nullable();
            $table->decimal('basic_salary', 15, 2)->nullable();
            $table->decimal('allowance_jabatan', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('job_title_id')->nullable()->constrained('job_titles');
            $table->decimal('min_salary', 15, 2)->nullable();
            $table->decimal('max_salary', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
