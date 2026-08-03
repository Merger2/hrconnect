<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The app writes statuses that the original CHECK constraints did not
     * allow (appraisal workflow moves through 'self_assessment' →
     * 'manager_review' → '1on1_scheduled' → 'completed'; attendance
     * corrections support 'pending_admin' while a supervisor-approved
     * correction waits for HR review). Extend both constraints to match.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE appraisals DROP CONSTRAINT IF EXISTS appraisals_status_check');

        DB::statement(<<<'SQL'
            ALTER TABLE appraisals ADD CONSTRAINT appraisals_status_check
            CHECK (status IN ('draft','submitted','self_assessment','manager_review','1on1_scheduled','completed','approved','rejected'))
        SQL);

        DB::statement('ALTER TABLE attendance_corrections DROP CONSTRAINT IF EXISTS attendance_corrections_status_check');

        DB::statement(<<<'SQL'
            ALTER TABLE attendance_corrections ADD CONSTRAINT attendance_corrections_status_check
            CHECK (status IN ('pending','pending_admin','approved','rejected'))
        SQL);
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE appraisals DROP CONSTRAINT IF EXISTS appraisals_status_check');

        DB::statement(<<<'SQL'
            ALTER TABLE appraisals ADD CONSTRAINT appraisals_status_check
            CHECK (status IN ('draft','submitted','approved','rejected'))
        SQL);

        DB::statement('ALTER TABLE attendance_corrections DROP CONSTRAINT IF EXISTS attendance_corrections_status_check');

        DB::statement(<<<'SQL'
            ALTER TABLE attendance_corrections ADD CONSTRAINT attendance_corrections_status_check
            CHECK (status IN ('pending','approved','rejected'))
        SQL);
    }
};
