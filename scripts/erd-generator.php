<?php

/**
 * HRConnect DBML generator — reads the live PostgreSQL schema and emits
 * a complete DBML file (dbdiagram.io compatible) with:
 *   - Project header + TableGroups per Release-1 module
 *   - All tables + columns (accurate types via format_type, incl. vector(768))
 *   - Primary keys, not-null, defaults
 *   - Foreign key relations (single-column) as global Ref lines
 *
 * Usage:
 *   php scripts/erd-generator.php > docs/hrconnect-schema.dbml
 */

// ── read DB credentials from .env ────────────────────────────────────────────
$env = [];
if (is_file('.env')) {
    foreach (file('.env') as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $env[trim($key)] = trim(trim($value), '"');
    }
}

$dbname = $env['DB_DATABASE'] ?? 'hris_payroll';
$dbuser = $env['DB_USERNAME'] ?? 'postgres';
$dbpass = $env['DB_PASSWORD'] ?? 'password';
$dbhost = $env['DB_HOST'] ?? '127.0.0.1';
$dbport = $env['DB_PORT'] ?? '5432';

$conn = pg_connect("host={$dbhost} port={$dbport} dbname={$dbname} user={$dbuser} password={$dbpass}");
if (! $conn) {
    exit('Connection failed to '.$dbname);
}

// ── table → module mapping (Release-1 modules; unmapped → "Lainnya") ────────
$modules = [
    '1. Master Data Karyawan' => [
        'users', 'employees', 'divisions', 'job_levels', 'job_titles', 'positions',
        'shifts', 'educations', 'family_details', 'branches', 'companies',
        'company_branches', 'company_settings', 'sites', 'wilayah',
        'indonesia_provinces', 'indonesia_cities', 'indonesia_districts', 'indonesia_villages',
    ],
    '2. Absensi & Jadwal' => [
        'attendances', 'attendance_corrections', 'attendance_offline_submissions',
        'schedules', 'face_descriptors', 'geolocation_logs', 'devices', 'holidays',
    ],
    '3. Cuti & Approval' => [
        'leaves', 'leave_balances', 'leave_types', 'overtimes',
        'work_from_home_requests', 'shift_swap_requests', 'approvals',
        'approval_matrix_rules', 'reimbursements', 'reimbursement_categories',
        'cash_advances', 'loans', 'loan_installments',
    ],
    '4. Payroll & Payslip' => [
        'payrolls', 'payroll_components', 'payroll_items', 'payroll_adjustments',
        'payroll_audits', 'tarif_ter', 'kategori_ter', 'golongan_ptkp',
        'golongan_ptkps', 'bpjs_configs',
    ],
    '5. Dokumen & HR Checklist' => [
        'employee_documents', 'employee_document_requests', 'employee_document_templates',
        'employee_document_types', 'hr_checklist_cases', 'hr_checklist_tasks',
        'hr_checklist_templates', 'hr_checklist_template_items', 'cloud_files',
    ],
    '6. Reports & Import/Export' => [
        'import_export_runs', 'import_progress', 'activity_log',
        'activity_log_details', 'activity_logs', 'system_backup_runs',
    ],
    '7. AI Knowledge Base' => [
        'knowledge_bases', 'knowledge_base_categories', 'knowledge_base_chunks',
        'chat_sessions', 'chat_messages', 'chat_messages_rag',
        'agent_conversations', 'agent_conversation_messages',
        'chat_threads', 'chat_thread_user', 'blind_indexes',
    ],
    '8. Auth & Infrastruktur' => [
        'roles', 'permissions', 'model_has_roles', 'model_has_permissions',
        'role_has_permissions', 'password_reset_tokens', 'sessions',
        'personal_access_tokens', 'notifications', 'user_notification_preferences',
        'jobs', 'job_batches', 'failed_jobs', 'cache', 'cache_locks',
        'migrations', 'settings', 'teams', 'team_invitations', 'team_user',
        'integration_clients', 'integration_endpoints', 'integration_deliveries',
        'integration_attendance_events',
    ],
];

$tableModule = [];
foreach ($modules as $module => $tables) {
    foreach ($tables as $t) {
        $tableModule[$t] = $module;
    }
}

// ── fetch all tables ─────────────────────────────────────────────────────────
$tables = pg_fetch_all(pg_query($conn, "
    SELECT table_name
    FROM information_schema.tables
    WHERE table_schema='public' AND table_type='BASE TABLE'
    ORDER BY table_name
"));
$tableNames = array_map(fn ($t) => $t['table_name'], $tables);

// sort by module, then alphabetically
usort($tableNames, function ($a, $b) use ($tableModule) {
    $ma = $tableModule[$a] ?? 'ZZ. Lainnya';
    $mb = $tableModule[$b] ?? 'ZZ. Lainnya';
    if ($ma === $mb) {
        return strcmp($a, $b);
    }

    return strcmp($ma, $mb);
});

// ── helpers ──────────────────────────────────────────────────────────────────
function pg_quote(string $s): string
{
    return "'".str_replace("'", "''", $s)."'";
}

function dbml_type(string $pgType): string
{
    // normalize timestamp(0)/time(0) precision — dbdiagram hanya kenal timestamp
    $pgType = preg_replace('/\((?:0|[0-9]+)\)/', '', $pgType);

    $map = [
        'character varying' => 'varchar',
        'integer' => 'int',
        'bigint' => 'bigint',
        'smallint' => 'smallint',
        'numeric' => 'numeric',
        'double precision' => 'float',
        'real' => 'float',
        'boolean' => 'bool',
        'text' => 'text',
        'date' => 'date',
        'time without time zone' => 'time',
        'time with time zone' => 'timetz',
        'timestamp without time zone' => 'timestamp',
        'timestamp with time zone' => 'timestamptz',
        'uuid' => 'uuid',
        'jsonb' => 'jsonb',
        'json' => 'json',
        'bytea' => 'bytea',
        'inet' => 'inet',
        'cidr' => 'cidr',
        'macaddr' => 'macaddr',
    ];
    foreach ($map as $pg => $dbml) {
        if ($pgType === $pg || str_starts_with($pgType, $pg.'(')) {
            return $dbml.substr($pgType, strlen($pg));
        }
    }

    // vector(768), enums, custom types — keep as-is
    return $pgType;
}

function dbml_default(?string $expr): ?string
{
    if ($expr === null) {
        return null;
    }
    // serial identity
    if (str_starts_with($expr, 'nextval(')) {
        return 'increment';
    }
    $expr = trim($expr);
    // function calls / expressions → backtick
    if (preg_match('/^[a-z_][a-z0-9_]*\(.*\)$/i', $expr) || str_contains($expr, '::')) {
        // strip ::type casts, keep the literal
        $clean = preg_replace('/::[a-z0-9_ ]+$/i', '', $expr);
        $clean = trim($clean, "'");
        if ($clean === 'true') {
            return 'true';
        }
        if ($clean === 'false') {
            return 'false';
        }
        if (is_numeric($clean)) {
            return $clean;
        }
        if (preg_match('/^[a-z_][a-z0-9_]*\(.*\)$/i', $clean)) {
            return "`{$clean}`";
        }

        return "'{$clean}'";
    }
    if ($expr === 'true') {
        return 'true';
    }
    if ($expr === 'false') {
        return 'false';
    }
    if (is_numeric($expr)) {
        return $expr;
    }

    return "'".str_replace("'", "\\'", $expr)."'";
}

// ── fetch columns / pk / fk ──────────────────────────────────────────────────
$columnCache = [];
$pkCache = [];

foreach ($tableNames as $table) {
    $res = pg_query_params($conn, '
        SELECT a.attname, format_type(a.atttypid, a.atttypmod) AS type,
               a.attnotnull, a.attnum,
               pg_get_expr(d.adbin, d.adrelid) AS default_expr
        FROM pg_attribute a
        LEFT JOIN pg_attrdef d ON d.adrelid = a.attrelid AND d.adnum = a.attnum
        WHERE a.attrelid = $1::regclass AND a.attnum > 0 AND NOT a.attisdropped
        ORDER BY a.attnum
    ', [$table]);
    $columnCache[$table] = pg_fetch_all($res) ?: [];

    $res = pg_query_params($conn, '
        SELECT a.attname
        FROM pg_index i
        JOIN pg_attribute a ON a.attrelid = i.indrelid AND a.attnum = ANY(i.indkey)
        WHERE i.indrelid = $1::regclass AND i.indisprimary
    ', [$table]);
    $pkCache[$table] = array_map(fn ($r) => $r['attname'], pg_fetch_all($res) ?: []);
}

$refs = pg_fetch_all(pg_query($conn, "
    SELECT con.conname, rel1.relname AS src_table, att1.attname AS src_col,
           rel2.relname AS dst_table, att2.attname AS dst_col
    FROM pg_constraint con
    JOIN pg_class rel1 ON rel1.oid = con.conrelid
    JOIN pg_class rel2 ON rel2.oid = con.confrelid
    JOIN pg_attribute att1 ON att1.attrelid = con.conrelid AND att1.attnum = con.conkey[1]
    JOIN pg_attribute att2 ON att2.attrelid = con.confrelid AND att2.attnum = con.confkey[1]
    WHERE con.contype = 'f'
      AND con.conkey IS NOT NULL AND array_length(con.conkey, 1) = 1
    ORDER BY con.conname
")) ?: [];

// ── emit DBML ────────────────────────────────────────────────────────────────
echo "// HRConnect — Database Schema (DBML)\n";
echo '// Generated: '.date('Y-m-d H:i')." from live PostgreSQL schema\n";
echo '// Tables: '.count($tableNames)." · Module grouping per Release-1 PRD\n\n";
echo "Project HRConnect {\n";
echo "  database_type: 'PostgreSQL'\n";
echo "  note: 'Laravel 13 + Livewire 4 + PostgreSQL 15 (pgvector 768D embeddings, pg_trgm, pgcrypto)'\n";
echo "}\n\n";

$currentModule = null;
foreach ($tableNames as $table) {
    $module = $tableModule[$table] ?? 'Lainnya (di luar 7 modul Release-1)';
    if ($module !== $currentModule) {
        $currentModule = $module;
        echo "// ── {$currentModule} ─────────────────────────────────────────\n";
    }
    echo "Table {$table} {\n";
    foreach ($columnCache[$table] as $col) {
        $attrs = [];
        if (in_array($col['attname'], $pkCache[$table], true)) {
            $attrs[] = 'pk';
        }
        $default = dbml_default($col['default_expr']);
        if ($default === 'increment') {
            $attrs[] = 'increment';
        } elseif ($default !== null) {
            $attrs[] = "default: {$default}";
        }
        if ($col['attnotnull'] === 't') {
            $attrs[] = 'not null';
        }
        $attrStr = $attrs ? ' ['.implode(', ', $attrs).']' : '';
        echo '  '.$col['attname'].' '.dbml_type($col['type']).$attrStr."\n";
    }
    echo "}\n\n";
}

// TableGroups per module
foreach ($modules as $module => $tables) {
    echo "TableGroup \"{$module}\" {\n";
    foreach ($tables as $t) {
        if (in_array($t, $tableNames, true)) {
            echo "  {$t}\n";
        }
    }
    echo "}\n\n";
}

// foreign keys
echo "// ── Relations ───────────────────────────────────────────────────────────\n";
foreach ($refs as $ref) {
    echo "Ref: {$ref['src_table']}.{$ref['src_col']} > {$ref['dst_table']}.{$ref['dst_col']}\n";
}

pg_close($conn);
