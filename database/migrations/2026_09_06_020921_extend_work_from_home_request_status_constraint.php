<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * WorkFromHomeRequest workflow now supports L1/L2 approval levels:
     * pending → approved_l1 → approved (or rejected at any stage).
     * Extend constraint to match WorkFromHomeRequestService::approve() + finalize().
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE work_from_home_requests DROP CONSTRAINT IF EXISTS work_from_home_requests_status_check');

        DB::statement(<<<'SQL'
            ALTER TABLE work_from_home_requests ADD CONSTRAINT work_from_home_requests_status_check
            CHECK (status IN ('pending','approved_l1','approved','rejected'))
        SQL);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement('ALTER TABLE work_from_home_requests DROP CONSTRAINT IF EXISTS work_from_home_requests_status_check');

        DB::statement(<<<'SQL'
            ALTER TABLE work_from_home_requests ADD CONSTRAINT work_from_home_requests_status_check
            CHECK (status IN ('pending','approved','rejected'))
        SQL);
    }
};
