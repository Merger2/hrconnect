<?php

/**
 * Audit preventif pola bug /knowledge-base (500: "Attempt to read property
 * \"value\" on string") — kolom model diakses method objek (->value, ->label(),
 * ->toDateString(), ->format()) TANPA cast yang sesuai.
 *
 * Output: SEMUA referensi pola berikut di kode
 * (blade/resource/controller/export/livewire) + status cast utk kolom tsb
 * di SEMUA model yang punya kolom itu:
 *
 *   1. `-><col>->value` / `-><col>?->value`   → butuh cast ENUM
 *   2. `-><col>->label()` / `-><col>?->label()` → butuh cast ENUM
 *   3. `-><col>->toDateString()` / `-><col>->format(` → butuh cast date/datetime
 *
 * ⚠️ SEMANTIK: agregasi PER-KOLOM, bukan per-variabel. Kolom dianggap aman
 * jika ADA minimal SATU model yg me-cast kolom itu dengan tipe kompatibel
 * (mis. `date` di-cast di Schedule/Overtime/Attendance → referensi
 * `$leave->date->format()` pun TIDAK di-flag, karena Leave punya kolom
 * tanggal tapi bernama start_date/end_date). Artinya:
 *   - Exit 1 (CI fail) hanya utk kolom yang TIDAK di-cast DI MANA PUN.
 *   - Kolom yang salah nama (bukan kolom model sama sekali, mis. `starts_at`
 *     padahal kolomnya `start_time`) TIDAK terdeteksi oleh cast matching —
 *     perlu review manual per-referensi (seperti audit 2026-08-12 yang
 *     menemukan 2 bug nyata, lihat bawah).
 *
 * Timestamp otomatis Eloquent (created_at/updated_at/deleted_at) SELALU
 * dianggap aman utk pola date — di-cast datetime oleh framework tanpa
 * perlu deklarasi di casts().
 *
 * Hasil audit 2026-08-12 (perluasan pola; verifikasi manual SEMUA referensi):
 *   - Pola ->value: bersih (cast enum semua + guard defensif) — lihat audit
 *     sebelumnya (commit ea58174).
 *   - Pola ->label(): bersih — semua ref `status`/`level` ber-cast enum atau
 *     guard `instanceof \BackedEnum`.
 *   - Pola date: 1 bug nyata DITEMUKAN & DI-FIX + 2 diinvestigasi:
 *     (1) collaboration-workspace.blade.php — `$meeting->starts_at` (kolom
 *         sebenarnya `start_time` di OnlineMeeting) → jam meeting tak pernah
 *         tampil (silent). Fix: start_time. ✅
 *     (2) leave-approval.blade.php — `$firstLeave->date->format()` DIPERIKSA
 *         & AMAN: meski variabel bernama `$firstLeave`, grup berisi model
 *         ATTENDANCE (M11 single-source: izin/cuti disimpan di attendances),
 *         dan Attendance me-cast `date => date`. Percobaan ubah ke start_date
 *         malah regression (3 test gagal) — JANGAN diubah. ⚠️ pelajaran:
 *         nama variabel bisa menyesatkan; selalu cek tipe data aktual.
 *     (3) DocumentTemplateRenderService.php — `$employee->hire_date?->format()`
 *         (Employee tak punya hire_date; kolomnya join_date) → tag dokumen
 *         selalu '-' (silent). Fix: join_date. ✅
 *     Semua referensi lain terverifikasi ber-cast date/datetime di model
 *     pemiliknya (Attendance, Leave, Overtime, Reimbursement, CompanyAsset,
 *     AssetHandover, Employee, LoanInstallment, AttendanceCorrection,
 *     LeaveBalance, EmployeeDocumentRequest, ProjectTask, Schedule,
 *     OnlineMeeting) atau timestamp otomatis (created_at).
 *
 * Accessor fluent (Attribute::get) TIDAK terdeteksi oleh parse casts —
 * whitelist manual di $accessorAliases di bawah utk kolom aksesori yang
 * TERVERIFIKASI mengembalikan objek yang tepat (mis. Attendance.time_in/
 * time_out = alias clock_in/clock_out). Tambahkan ke whitelist HANYA setelah
 * verifikasi definisi accessor di model.
 */
$root = dirname(__DIR__);

// Diperlukan utk enum_exists() resolve class enum (autoload composer)
require $root.'/vendor/autoload.php';

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

// Kumpulkan casts + peta use (short-name → FQCN) dari semua model
$modelCasts = [];
$modelUses = [];
foreach (glob("$root/app/Models/*.php") ?: [] as $modelFile) {
    $base = basename($modelFile, '.php');
    if (str_starts_with($base, '_ide_helper')) {
        continue;
    }
    $content = file_get_contents($modelFile);
    $casts = [];
    // Peta use: `use App\Enums\Foo;` → Foo => App\Enums\Foo
    $useMap = [];
    if (preg_match_all('/^use\s+([A-Za-z0-9_\\\\]+)(?:\s+as\s+([A-Za-z0-9_]+))?;/m', $content, $um, PREG_SET_ORDER)) {
        foreach ($um as $u) {
            $fqcn = ltrim($u[1], '\\');
            $alias = $u[2] ?? substr($fqcn, strrpos($fqcn, '\\') + 1);
            $useMap[$alias] = $fqcn;
        }
    }
    $modelUses[$base] = $useMap;
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

// Definisi pola yang di-scan
$patterns = [
    'value' => [
        'regex' => '/->([a-z_]+)\?*->value\b/',
        'method' => '->value',
        'required' => 'cast ENUM',
        'isEnumCast' => true,
    ],
    'label' => [
        'regex' => '/->([a-z_]+)\?*->label\(\)/',
        'method' => '->label()',
        'required' => 'cast ENUM',
        'isEnumCast' => true,
    ],
    'date' => [
        'regex' => '/->([a-z_]+)\?*->(?:toDateString\(\)|format\()/',
        'method' => '->toDateString() / ->format()',
        'required' => 'cast date/datetime',
        'isEnumCast' => false,
    ],
];

// Kolom aksesori (fluent Attribute::get) yang TERVERIFIKASI aman — di-parse
// manual karena tidak muncul di casts() model. Tambahkan hanya setelah cek
// definisi accessor di model pemiliknya.
$accessorAliases = [
    'time_in' => 'Attendance::timeIn() — alias clock_in (cast datetime)',
    'time_out' => 'Attendance::timeOut() — alias clock_out (cast datetime)',
];

$exitCode = 0;
$failColumns = []; // pattern => [cols]

foreach ($patterns as $patternKey => $pattern) {
    // Kumpulkan referensi per kolom utk pola ini
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
            if (preg_match_all($pattern['regex'], $line, $m)) {
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

    $totalRefs = array_sum(array_map('count', $refs));
    echo "\n".'POLA '.$pattern['method'].' — butuh '.$pattern['required'].'  ('.$totalRefs.' total)'.PHP_EOL;
    echo str_repeat('=', 120).PHP_EOL;

    foreach ($refs as $col => $locations) {
        // Model yang punya kolom ini + cast-nya
        $castInfo = [];
        foreach ($modelCasts as $model => $casts) {
            if (array_key_exists($col, $casts)) {
                $castInfo[] = ['model' => $model, 'cast' => $casts[$col]];
            }
        }

        // Timestamp otomatis Eloquent selalu aman utk pola date
        $autoTimestamp = ! $pattern['isEnumCast'] && in_array($col, ['created_at', 'updated_at', 'deleted_at'], true);

        // Accessor fluent terverifikasi (manual whitelist)
        $knownAccessor = array_key_exists($col, $accessorAliases);

        // Kompatibel jika ada cast dengan tipe yang cocok di model mana pun
        $compatible = $autoTimestamp || $knownAccessor;
        if (! $compatible) {
            foreach ($castInfo as $info) {
                $castValue = trim($info['cast']);
                if ($pattern['isEnumCast']) {
                    // Deteksi cast enum via enum_exists() (nama class bisa tak
                    // mengandung kata "Enum", mis. AttendanceStatus::class)
                    if (preg_match('/([A-Za-z_\\\\]+)::class/', $castValue, $em)) {
                        $short = ltrim($em[1], '\\');
                        // Resolve short-name (import di model) → FQCN
                        $fqcn = $short;
                        if (! str_contains($fqcn, '\\')) {
                            $fqcn = $modelUses[$info['model']][$short] ?? 'App\\Enums\\'.$short;
                        }
                        if (enum_exists($fqcn)) {
                            $compatible = true;
                            break;
                        }
                    }
                } else {
                    if (str_contains($castValue, 'date') || str_contains($castValue, 'datetime') || str_contains($castValue, 'timestamp')) {
                        $compatible = true;
                        break;
                    }
                }
            }
        }

        printf("\n■ Kolom: %s  (%d refs)   casts: %s%s\n",
            $col,
            count($locations),
            $castInfo
                ? implode(' | ', array_map(fn ($ci) => $ci['model'].' => '.$ci['cast'], $castInfo))
                : ($autoTimestamp ? '(timestamp otomatis Eloquent)' : ($knownAccessor ? '('.$accessorAliases[$col].')' : '(TIDAK ada model dgn cast utk kolom ini!)')),
            $compatible ? '  ✅' : '  ⚠️'
        );
        foreach (array_slice($locations, 0, 10) as $loc) {
            echo "    $loc".PHP_EOL;
        }
        if (count($locations) > 10) {
            echo '    … +'.(count($locations) - 10).' lagi'.PHP_EOL;
        }

        if (! $compatible) {
            // Kolom diakses method objek TANPA cast kompatibel di model mana
            // pun = pola bug /knowledge-base (500 "Attempt to read property
            // value on string" / "Call to a member function X on string").
            // Catatan: referensi dgn guard defensif (instanceof / ?? /
            // is_string) atau variabel non-model (enum::cases()) di-check
            // manual saat audit; flag di sini memaksa review manusia.
            $exitCode = 1;
            $failColumns[$patternKey][] = $col;
        }
    }
}

if ($exitCode !== 0) {
    echo PHP_EOL.'❌ TEMUAN: kolom diakses method objek tanpa cast sesuai:'.PHP_EOL;
    foreach ($failColumns as $patternKey => $cols) {
        echo '   ['.$patterns[$patternKey]['method'].'] '.implode(', ', array_unique($cols)).PHP_EOL;
    }
    echo '   Periksa apakah benar-benar bug (pola /knowledge-base) atau guard defensif / variabel non-model yg perlu whitelist.'.PHP_EOL;
}

exit($exitCode);
