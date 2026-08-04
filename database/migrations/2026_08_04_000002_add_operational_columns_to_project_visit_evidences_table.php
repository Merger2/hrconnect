<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * project_visit_evidences awalnya dibuat dengan skema spatie activitylog
     * (hanya project_id + kolom activitylog), padahal pemakaian aktual menulis
     * dan membaca kolom operational: OperationalWorkspaceService::recordVisitEvidence(),
     * relasi ProjectTask::visitEvidences()/Project::visitEvidences() (project_task_id,
     * orderBy visited_at), dan blade MyOperationalTasks/OperationalWorkspace
     * (visited_at, notes, photo_path). Migration tambahan ini menambahkan kolom
     * yang diquery/diisi — migration asli (2026_07_21_000014_051030) tidak diubah
     * karena sudah jalan di DB dev.
     */
    public function up(): void
    {
        Schema::table('project_visit_evidences', function (Blueprint $table) {
            $table->foreignId('project_task_id')->nullable()->after('project_id')->constrained('project_tasks')->nullOnDelete();
            $table->foreignId('company_id')->nullable()->after('project_task_id')->constrained('companies');
            $table->timestamp('visited_at')->nullable();
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->integer('accuracy_meters')->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->string('photo_disk', 50)->nullable();
            $table->string('photo_path', 2048)->nullable();
            $table->string('photo_original_name', 255)->nullable();
            $table->json('metadata')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('project_visit_evidences', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_task_id');
            $table->dropConstrainedForeignId('company_id');
            $table->dropColumn([
                'visited_at',
                'latitude',
                'longitude',
                'accuracy_meters',
                'address',
                'notes',
                'photo_disk',
                'photo_path',
                'photo_original_name',
                'metadata',
            ]);
        });
    }
};
