<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tarif_ter', function (Blueprint $table) {
            $table->id();
            $table->decimal('penghasilan_min', 15, 2);
            $table->decimal('penghasilan_max', 15, 2)->nullable();
            $table->decimal('tarif_a', 5, 2);
            $table->decimal('tarif_b', 5, 2);
            $table->decimal('tarif_c', 5, 2);
            $table->timestamps();
        });

        DB::table('tarif_ter')->insert([
            ['penghasilan_min' => 0, 'penghasilan_max' => 5400000, 'tarif_a' => 0, 'tarif_b' => 0, 'tarif_c' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['penghasilan_min' => 5400001, 'penghasilan_max' => 6000000, 'tarif_a' => 0.05, 'tarif_b' => 0, 'tarif_c' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['penghasilan_min' => 6000001, 'penghasilan_max' => 7500000, 'tarif_a' => 0.1, 'tarif_b' => 0, 'tarif_c' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['penghasilan_min' => 7500001, 'penghasilan_max' => 9000000, 'tarif_a' => 0.15, 'tarif_b' => 0, 'tarif_c' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['penghasilan_min' => 9000001, 'penghasilan_max' => 10500000, 'tarif_a' => 0.2, 'tarif_b' => 0, 'tarif_c' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['penghasilan_min' => 10500001, 'penghasilan_max' => 12000000, 'tarif_a' => 0.25, 'tarif_b' => 0, 'tarif_c' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['penghasilan_min' => 12000001, 'penghasilan_max' => 13500000, 'tarif_a' => 0.3, 'tarif_b' => 0, 'tarif_c' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['penghasilan_min' => 13500001, 'penghasilan_max' => 15000000, 'tarif_a' => 0.3, 'tarif_b' => 0, 'tarif_c' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['penghasilan_min' => 15000001, 'penghasilan_max' => 16500000, 'tarif_a' => 0.35, 'tarif_b' => 0, 'tarif_c' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['penghasilan_min' => 16500001, 'penghasilan_max' => 18000000, 'tarif_a' => 0.35, 'tarif_b' => 0, 'tarif_c' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['penghasilan_min' => 18000001, 'penghasilan_max' => 19500000, 'tarif_a' => 0.4, 'tarif_b' => 0, 'tarif_c' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['penghasilan_min' => 19500001, 'penghasilan_max' => 21000000, 'tarif_a' => 0.4, 'tarif_b' => 0, 'tarif_c' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('tarif_ter');
    }
};
