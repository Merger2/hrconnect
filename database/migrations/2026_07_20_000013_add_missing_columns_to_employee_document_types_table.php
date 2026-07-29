<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employee_document_types', function (Blueprint $table) {
            $table->string('code')->nullable()->change(); // slug already unique
            $table->integer('retention_days')->nullable()->after('is_required');
            $table->string('category')->nullable()->after('retention_days');
            $table->boolean('requires_employee_upload')->default(false)->after('category');
            $table->boolean('auto_generate_enabled')->default(false)->after('requires_employee_upload');
            $table->boolean('is_active')->default(true)->after('auto_generate_enabled');
            $table->boolean('admin_requestable')->default(false)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('employee_document_types', function (Blueprint $table) {
            $table->dropColumn([
                'retention_days',
                'category',
                'requires_employee_upload',
                'auto_generate_enabled',
                'is_active',
                'admin_requestable',
            ]);
        });
    }
};
