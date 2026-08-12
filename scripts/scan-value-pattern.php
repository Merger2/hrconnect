<?php

/**
 * Audit preventif pola bug /knowledge-base (500: "Attempt to read property
 * \"value\" on string") — kolom model diakses `->value` TANPA cast enum.
 *
 * Output: SEMUA referensi `-><col>->value` / `-><col>?->value` di kode
 * (blade/resource/controller/export/livewire) + status cast enum utk kolom
 * tsb di SEMUA model yang punya kolom itu.
 *
 * ⚠️ SEMANTIK: agregasi PER-KOLOM, bukan per-variabel. Kolom dianggap aman
 * jika ADA minimal SATU model yg me-cast kolom itu ke enum (mis. `status`
 * di-cast di 11 model → referensi `$cashAdvance->status->value` pun TIDAK
 * di-flag, karena CashAdvance punya kolom status tanpa cast). Artinya:
 *   - Exit 1 (CI fail) hanya utk kolom yang TIDAK di-cast DI MANA PUN.
 *   - Referensi ->value pada model TANPA cast utk kolom yang SUDAH di-cast
 *     di model lain (pola bug nyata) TIDAK terdeteksi otomatis — perlu
 *     review manual (seperti audit 2026-08-12) atau guard defensif
 *     (instanceof / ?? / is_string) yg diverifikasi per-referensi.
 *
 * Hasil audit 2026-08-12 (commit ea58174 + verifikasi manual 57 referensi):
 *   - Satu-satunya bug pola ini: KnowledgeBase.category (tanpa cast) →
 *     SUDAH di-fix dgn cast KnowledgeBaseCategoryEnum.
 *   - Semua referensi ->status->value aktual menyentuh model ber-cast enum
 *     (Attendance/Payroll/Leave/Reimbursement/Approval/Loan/Overtime/Asset/
 *     Employee/KnowledgeBase) atau guard defensif (instanceof / ?? / is_string).
 *   - level->value selalu dgn guard `instanceof \BackedEnum`.
 *   - name->value hanya di BpjsConfig (cast BpjsType + guard).
 *   - Model dgn kolom status/category TANPA cast (CashAdvance, dll) TIDAK
 *     pernah diakses ->value — blade pakai statusLabel()/match/(string).
 */

$root = dirname(__DIR__);

$scanDirs = [
    "$root/resources/views",
    "$root/app/Http/Resources",
    "$root/app/Http/Controllers/Api",
    "$root/app/Exports",
    "$root/app/Livewire",
    "$root/app/Support",
    "$root/app/Services",
    "$root/app/Policies",
    "$root/app/Notifications",
];

$allFiles = [];
foreach ($scanDirs as $dir) {
    foreach (glob("$dir/*.php") ?: [] as $f) {
        $allFiles[] = $f;
    }
    foreach (glob("$dir/**/*.php") ?: [] as $f) {
        $allFiles[] = $f;
    }
}
foreach (glob("$root/resources/views/**/*.blade.php") ?: [] as $f) {
    $allFiles[] = $f;
}
$allFiles = array_values(array_unique($allFiles));

// Kumpulkan casts dari semua model
$modelCasts = [];
foreach (glob("$root/app/Models/*.php") ?: [] as $modelFile) {
    $base = basename($modelFile, '.php');
    if (str_starts_with($base, '_ide_helper')) {
        continue;
    }
    $content = file_get_contents($modelFile);
    $casts = [];
    if (preg_match('/function casts\(\)\s*(?::[^{]+)?\{.*?\n(.*?)\n\s*\}/s', $content, $m)) {
        $block = $m[1];
    } elseif (preg_match('/\$casts\s*=\s*\[(.*?)\];/s', $content, $m)) {
        $block = $m[1];
    } else {
        $block = '';
    }
    if (preg_match_all("/'([a-z_]+)'\s*=>\s*([^,\n]+)/", $block, $m2)) {
        foreach ($m2[1] as $i => $col) {
            $casts[$col] = trim($m2[2][$i]);
        }
    }
    $modelCasts[$base] = $casts;
}

// Kumpulkan referensi ->col->value / ->col?->value
$refs = []; // col => list of file:line: snippet
foreach ($allFiles as $file) {
    if (str_contains($file, 'codemap') || str_contains($file, '_ide_helper')) {
        continue;
    }
    $lines = file($file);
    if ($lines === false) {
        continue;
    }
    foreach ($lines as $i => $line) {
        if (str_contains($line, '->values(')) {
            continue;
        }
        if (preg_match_all('/->([a-z_]+)\?*->value\b/', $line, $m)) {
            foreach ($m[1] as $col) {
                $snippet = trim($line);
                if (strlen($snippet) > 100) {
                    $snippet = substr($snippet, 0, 97).'...';
                }
                $refs[$col][] = sprintf(
                    '%s:%d  %s',
                    str_replace($root.'/', '', $file),
                    $i + 1,
                    $snippet
                );
            }
        }
    }
}

ksort($refs);

echo 'REFERENSI ->value DI KODE ('.array_sum(array_map('count', $refs)).' total)'.PHP_EOL;
echo str_repeat('=', 120).PHP_EOL;

$exitCode = 0;
$uncastColumns = [];

foreach ($refs as $col => $locations) {
    // Model yang punya kolom ini + cast-nya
    $castInfo = [];
    foreach ($modelCasts as $model => $casts) {
        if (array_key_exists($col, $casts)) {
            $castInfo[] = $model.' => '.$casts[$col];
        }
    }

    printf("\n■ Kolom: %s  (%d refs)   casts: %s%s\n",
        $col,
        count($locations),
        $castInfo ? implode(' | ', $castInfo) : '(TIDAK ada model dgn cast utk kolom ini!)',
        $castInfo ? '  ✅' : '  ⚠️'
    );
    foreach (array_slice($locations, 0, 10) as $loc) {
        echo "    $loc".PHP_EOL;
    }
    if (count($locations) > 10) {
        echo '    … +'.(count($locations) - 10).' lagi'.PHP_EOL;
    }

    if (! $castInfo) {
        // Kolom diakses ->value TANPA cast enum di model mana pun = pola bug
        // /knowledge-base (500 "Attempt to read property value on string").
        // Catatan: referensi dgn guard defensif (instanceof / ?? / is_string)
        // di-check manual saat audit; flag di sini memaksa review manusia.
        $exitCode = 1;
        $uncastColumns[] = $col;
    }
}

if ($exitCode !== 0) {
    echo PHP_EOL.'❌ TEMUAN: kolom diakses ->value tanpa cast enum: '.implode(', ', array_unique($uncastColumns)).PHP_EOL;
    echo '   Periksa apakah benar-benar bug (pola /knowledge-base) atau guard defensif yg perlu ditambahkan ke whitelist.'.PHP_EOL;
}

exit($exitCode);
