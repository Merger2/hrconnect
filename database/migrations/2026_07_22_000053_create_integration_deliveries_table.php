<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_deliveries', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('integration_endpoint_id')->constrained('integration_endpoints')->cascadeOnDelete();
            $table->text('payload_template')->nullable();
            $table->string('encryption_key')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_deliveries');
    }
};
