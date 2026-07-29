<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('leave_type_id')->nullable()->after('approval_status')->constrained('leave_types')->nullOnDelete();
            $table->text('note')->nullable()->after('leave_type_id');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('leave_type_id');
            $table->dropColumn('note');
        });
    }
};
