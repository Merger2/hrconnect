<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('golongan_ptkp') && ! Schema::hasColumn('golongan_ptkp', 'kategori_ter_id')) {
            Schema::drop('golongan_ptkp');
        }
    }

    public function down(): void
    {
        // Restore not needed — old table was abandoned data
    }
};
