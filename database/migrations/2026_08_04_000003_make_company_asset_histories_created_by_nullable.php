<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * company_asset_histories.created_by awalnya NOT NULL, tapi
     * UserAssetService::verifyReturnOtp() menulis `$user->employee?->id`
     * yang bisa null ketika user tidak punya Employee → QueryException.
     * Buat kolom nullable (FK tetap dipertahankan; NULL lolos FK).
     */
    public function up(): void
    {
        Schema::table('company_asset_histories', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreignId('created_by')->nullable()->change()->constrained('employees');
        });
    }

    public function down(): void
    {
        Schema::table('company_asset_histories', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->foreignId('created_by')->nullable(false)->change()->constrained('employees');
        });
    }
};
