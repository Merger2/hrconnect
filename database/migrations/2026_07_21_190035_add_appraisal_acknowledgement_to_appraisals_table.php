<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('appraisals', function (Blueprint $table) {
            $table->date('meeting_date')->nullable()->after('review_date');
            $table->boolean('employee_acknowledgement')->default(false)->after('notes');
            $table->foreignId('evaluator_id')->nullable()->constrained('users')->after('employee_acknowledgement');
            $table->foreignId('calibrator_id')->nullable()->constrained('users')->after('evaluator_id');
        });
    }

    public function down(): void
    {
        Schema::table('appraisals', function (Blueprint $table) {
            $table->dropColumn(['meeting_date', 'employee_acknowledgement', 'evaluator_id', 'calibrator_id']);
        });
    }
};
