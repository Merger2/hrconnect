<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Index kolom hot-path yang BELUM di-index (audit 2026-08-12 — berbasis
 * pg_indexes aktual + pola query aktif di kode).
 *
 * PostgreSQL TIDAK auto-index kolom FK (beda dgn MySQL/InnoDB) — setiap
 * filter/JOIN via `employee_id`/`user_id`/`status`/`company_id` tanpa index
 * = sequential scan di tabel besar (attendances-scale).
 *
 * Prioritas:
 *   P0 — tabel self-service karyawan (pola FK AGENTS.md: schedules/cash_advances/
 *        shift_swap_requests/work_from_home_requests = user_id; sisanya employee_id):
 *        cash_advances, shift_swap_requests, work_from_home_requests,
 *        attendance_corrections (employee_id + user_id), employee_document_requests.
 *   P1 — kolaborasi/tugas/aset: reimbursements(status), chat_messages(thread+user),
 *        chat_threads(company), project_tasks(project/assigned_to/status),
 *        cloud_files(company/project), company_asset_histories(3 FK), company_assets(status).
 *   P2 — integrasi/ops/RAG: integration_deliveries, import_export_runs,
 *        knowledge_base_chunks(knowledge_base_id).
 *
 * Semua pakai `CREATE INDEX IF NOT EXISTS` → idempotent (aman utk DB existing
 * maupun fresh migrate/CI). Nama index mengikuti pola `{table}_{column}_index`.
 */
return new class extends Migration
{
    /** @var array<string, list<string>> [tabel => kolom yang butuh index] */
    private array $indexes = [
        // P0 — tabel self-service karyawan (pola FK AGENTS.md)
        'cash_advances' => ['user_id', 'status'],
        'shift_swap_requests' => ['user_id', 'status'],
        'work_from_home_requests' => ['user_id', 'status'],
        'attendance_corrections' => ['employee_id', 'user_id', 'status'],
        'employee_document_requests' => ['employee_id', 'status'],

        // P1 — kolaborasi / tugas / aset
        'reimbursements' => ['status'],
        'chat_messages' => ['chat_thread_id', 'user_id'],
        'chat_threads' => ['company_id'],
        'project_tasks' => ['project_id', 'assigned_to', 'status'],
        'cloud_files' => ['company_id', 'project_id'],
        'company_asset_histories' => ['company_asset_id', 'from_employee_id', 'to_employee_id'],
        'company_assets' => ['status'],

        // P2 — integrasi / ops / RAG
        'integration_deliveries' => ['integration_endpoint_id', 'status'],
        'import_export_runs' => ['requested_by_user_id', 'status'],
        'knowledge_base_chunks' => ['knowledge_base_id'],
    ];

    public function up(): void
    {
        foreach ($this->indexes as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement(sprintf(
                    'CREATE INDEX IF NOT EXISTS %s ON %s ("%s")',
                    $this->indexName($table, $column),
                    $table,
                    $column
                ));
            }
        }
    }

    public function down(): void
    {
        foreach ($this->indexes as $table => $columns) {
            foreach ($columns as $column) {
                DB::statement(sprintf('DROP INDEX IF EXISTS %s', $this->indexName($table, $column)));
            }
        }
    }

    private function indexName(string $table, string $column): string
    {
        return "{$table}_{$column}_index";
    }
};
