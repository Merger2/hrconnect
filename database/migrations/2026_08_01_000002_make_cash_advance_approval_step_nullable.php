<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The approval matrix stores step keys as strings ('2', 'direct_manager',
     * or null when the workflow completes) — see CashAdvanceApprovalService.
     * The original column was integer NOT NULL DEFAULT 0, which rejected null
     * on final approval and coerced string keys to integers. Reimbursements
     * already use a nullable varchar; align cash_advances with that contract.
     */
    public function up(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE cash_advances
                ALTER COLUMN approval_current_step TYPE varchar(50)
                USING approval_current_step::varchar
        SQL);

        DB::statement('ALTER TABLE cash_advances ALTER COLUMN approval_current_step DROP NOT NULL');
        DB::statement('ALTER TABLE cash_advances ALTER COLUMN approval_current_step DROP DEFAULT');
    }

    public function down(): void
    {
        DB::statement(<<<'SQL'
            ALTER TABLE cash_advances
                ALTER COLUMN approval_current_step TYPE integer
                USING CASE
                    WHEN approval_current_step ~ '^[0-9]+$' THEN approval_current_step::integer
                    ELSE 0
                END
        SQL);

        DB::statement('ALTER TABLE cash_advances ALTER COLUMN approval_current_step SET NOT NULL');
        DB::statement('ALTER TABLE cash_advances ALTER COLUMN approval_current_step SET DEFAULT 0');
    }
};
