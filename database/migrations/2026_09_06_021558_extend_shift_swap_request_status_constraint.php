<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * ShiftSwapRequest workflow supports L1/L2 approval levels:
     * pending → approved_l1 → approved (or rejected at any stage).
     * Extend constraint to match ShiftSwapRequestService::approve() + finalize().
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE shift_swap_requests DROP CONSTRAINT IF EXISTS shift_swap_requests_status_check');

        DB::statement(<<<'SQL'
            ALTER TABLE shift_swap_requests ADD CONSTRAINT shift_swap_requests_status_check
            CHECK (status IN ('pending','approved_l1','approved','rejected'))
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE shift_swap_requests DROP CONSTRAINT IF EXISTS shift_swap_requests_status_check');

        DB::statement(<<<'SQL'
            ALTER TABLE shift_swap_requests ADD CONSTRAINT shift_swap_requests_status_check
            CHECK (status IN ('pending','approved','rejected'))
        SQL);
    }
};
