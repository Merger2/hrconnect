<?php

$conn = pg_connect('host=127.0.0.1 dbname=hris_payroll user=postgres password=password');
if (! $conn) {
    exit('Connection failed');
}

$tables = pg_fetch_all(pg_query($conn, "
    SELECT table_name 
    FROM information_schema.tables 
    WHERE table_schema='public' AND table_type='BASE TABLE'
    ORDER BY table_name
"));

if (! $tables) {
    exit('No tables found');
}

echo "// HRConnect ERD - Generated from PostgreSQL schema\n";
echo '// Date: '.date('Y-m-d')."\n";
echo '// Tables: '.count($tables)."\n\n";
echo "Project HRConnect {\n";
echo "  database_type: 'PostgreSQL'\n";
echo "  note: 'Laravel 13 + Livewire 4 + PostgreSQL + pgvector'\n";
echo "}\n\n";

foreach ($tables as $t) {
    $table = $t['table_name'];

    $cols = pg_fetch_all(pg_query($conn, "
        SELECT column_name, data_type, is_nullable, column_default, character_maximum_length,
               numeric_precision, numeric_scale, ordinal_position
        FROM information_schema.columns 
        WHERE table_schema='public' AND table_name='$table'
        ORDER BY ordinal_position
    "));

    $pk_cols = [];
    $pks = pg_fetch_all(pg_query($conn, "
        SELECT kcu.column_name
        FROM information_schema.table_constraints tc
        JOIN information_schema.key_column_usage kcu ON tc.constraint_name = kcu.constraint_name
        WHERE tc.table_schema='public' AND tc.table_name='$table' AND tc.constraint_type='PRIMARY KEY'
    "));
    if ($pks) {
        foreach ($pks as $pk) {
            $pk_cols[] = $pk['column_name'];
        }
    }

    echo "Table $table {\n";
    if ($cols) {
        foreach ($cols as $col) {
            $type = $col['data_type'];
            if ($col['character_maximum_length'] && $type === 'character varying') {
                $type = "varchar({$col['character_maximum_length']})";
            } elseif ($col['character_maximum_length'] && $type === 'character') {
                $type = "char({$col['character_maximum_length']})";
            } elseif ($type === 'numeric' && $col['numeric_precision']) {
                $type = $col['numeric_scale'] > 0 ? "numeric({$col['numeric_precision']},{$col['numeric_scale']})" : "numeric({$col['numeric_precision']})";
            }

            $line = "  {$col['column_name']} $type";
            if (in_array($col['column_name'], $pk_cols)) {
                $line .= ' [pk]';
            }
            if ($col['is_nullable'] === 'YES') {
                $line .= ',';
            }
            if ($col['column_default'] !== null) {
                $def = $col['column_default'];
                if ($type === 'boolean') {
                    $def = $def === 'true' ? 'true' : 'false';
                }
                $line .= " [default: $def]";
            }
            echo "$line\n";
        }
    }
    echo "}\n\n";
}

pg_close($conn);
