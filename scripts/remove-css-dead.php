<?php

/**
 * Hapus class CSS mati dari resources/css/app.css — HRConnect (2026-08-08).
 *
 * Logika deteksi IDENTIK dengan scripts/audit-css-dead.php (single source of
 * truth di komentar atas script tsb): class owner dianggap mati hanya jika
 * SEMUA bloknya punya 0 referensi di blade/js/vendor/public, dan selector-nya
 * tidak ter-escape (bukan utilities Tailwind).
 *
 * Keamanan:
 * - Dry-run default: cetak apa yang akan dihapus, TANPA menulis.
 * - --apply: backup ke /tmp/app.css.bak-<ts> lalu tulis ulang.
 * - Hapus per baris (verbatim — format file tidak diubah selain baris yang dibuang).
 * - Setelah apply, jalankan scripts/check-color-tokens.php + check-token-sync.php.
 *
 * Usage:
 *   php scripts/remove-css-dead.php            # dry-run
 *   php scripts/remove-css-dead.php --apply    # backup + hapus
 */

$t0 = microtime(true);
ini_set('memory_limit', '512M');
$apply = in_array('--apply', $argv, true);

$root = dirname(__DIR__);
$cssPath = $root.'/resources/css/app.css';
$css = file_get_contents($cssPath);
$cssLen = strlen($css);

// ---------- haystack (referensi class di blade/js/vendor/public) ----------
$haystack = '';
$scanDirs = [
    $root.'/resources/views',
    $root.'/resources/js',
    $root.'/vendor/laravel/framework/src/Illuminate/Pagination/resources/views',
    $root.'/vendor/laravel/jetstream',
    $root.'/storage/framework/views',
];
foreach ($scanDirs as $dir) {
    if (! is_dir($dir)) {
        continue;
    }
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    foreach ($it as $f) {
        if ($f->isFile() && preg_match('/\.(blade\.php|js|php)$/', $f->getFilename())) {
            $haystack .= "\n".file_get_contents($f->getPathname());
        }
    }
}
foreach (['public/offline.html', 'public/index.php', 'public/manifest.json'] as $pf) {
    if (is_file($root.'/'.$pf)) {
        $haystack .= "\n".file_get_contents($root.'/'.$pf);
    }
}

// ---------- tokenisasi ----------
$tokens = [];
$prefixes = [];
foreach (str_split($haystack, 262144) as $chunk) {
    preg_match_all('/[A-Za-z_][A-Za-z0-9_-]*/', $chunk, $m);
    foreach ($m[0] as $t) {
        if (! isset($tokens[$t])) {
            $acc = '';
            foreach (explode('-', $t) as $p) {
                $acc = $acc === '' ? $p : $acc.'-'.$p;
                $tokens[$acc] = true;
            }
        }
        if (str_ends_with($t, '-') && ! in_array($t, $prefixes, true)) {
            $prefixes[] = $t;
        }
    }
    unset($m);
}
$usedFn = function (string $c) use ($tokens, $prefixes): bool {
    if (isset($tokens[$c])) {
        return true;
    }
    foreach ($prefixes as $p) {
        if (str_starts_with($c, $p)) {
            return true;
        }
    }
    return false;
};

// ---------- parser CSS depth-aware (dengan offset absolut) ----------
function walkCssOffsets(string $css, int $base, callable $cb): void {
    $len = strlen($css);
    $depth = 0;
    $selStart = -1;
    $curSelStart = 0; // offset selector blok depth-0 saat ini (ditangkap saat '{')
    $blockStart = 0;
    $quote = null;
    $i = 0;
    while ($i < $len) {
        $ch = $css[$i];
        if ($ch === '/' && ($css[$i + 1] ?? '') === '*') {
            $end = strpos($css, '*/', $i + 2);
            $i = ($end === false) ? $len : $end + 2;
            continue;
        }
        if ($quote !== null) {
            if ($ch === '\\') {
                $i += 2;
                continue;
            }
            if ($ch === $quote) {
                $quote = null;
            }
            $i++;
            continue;
        }
        if ($ch === "'" || $ch === '"') {
            $quote = $ch;
            $i++;
            continue;
        }
        if ($ch === '{') {
            $selStartAbs = max(0, $selStart); // tangkap SEBELUM reset
            $sel = trim(preg_replace('/\s+/', ' ', substr($css, $selStartAbs, $i - $selStartAbs)));
            if ($depth === 0) {
                $blockStart = $i + 1;
                $curSelStart = $selStartAbs;
            }
            $depth++;
            $selStart = -1;
            $i++;
            continue;
        }
        if ($ch === '}') {
            if ($depth === 1) {
                $absStart = $base + $curSelStart;
                $absEnd = $base + $i;
                $body = substr($css, $blockStart, $i - $blockStart);
                if ($sel !== '') {
                    if (str_starts_with($sel, '@')) {
                        walkCssOffsets($body, $base + $blockStart, $cb);
                    } else {
                        $cb($sel, $absStart, $absEnd);
                    }
                }
            }
            $depth = max(0, $depth - 1);
            $i++;
            continue;
        }
        if ($depth === 0 && $selStart === -1 && ! ctype_space($ch)) {
            $selStart = $i;
        }
        $i++;
    }
}

// ---------- ekstrak @layer components ----------
if (! preg_match('/@layer components\s*\{(.*?)\}\s*@layer utilities/s', $css, $mComp, PREG_OFFSET_CAPTURE)) {
    fwrite(STDERR, "GAGAL: @layer components tidak ditemukan.\n");
    exit(1);
}
$body = $mComp[1][0];
$bodyStart = $mComp[1][1];

// ---------- index line-start (binary search) — substr_count per blok terlalu lambat ----------
$lineStarts = [0];
$idx = 0;
foreach (explode("\n", $css) as $l) {
    $idx += strlen($l) + 1;
    $lineStarts[] = $idx;
}
$lineOf = function (int $absOffset) use ($lineStarts): int {
    $lo = 0;
    $hi = count($lineStarts) - 1;
    while ($lo <= $hi) {
        $mid = intdiv($lo + $hi, 2);
        if ($lineStarts[$mid] <= $absOffset) {
            $lo = $mid + 1;
        } else {
            $hi = $mid - 1;
        }
    }
    return $hi + 1;
};

// ---------- kumpulkan blok components ----------
// Logika IDENTIK dengan audit-css-dead.php: blok terdaftar SEKALI dengan owner
// (class pertama). Yang boleh dihapus HANYA blok yang OWNER-nya mati — class
// non-owner (mis. ts-input / flatpickr-time / ptr--ptr yang di-inject library
// saat runtime) tidak pernah jadi owner, jadi tidak ikut terhapus.
$blocks = []; // list blok: owner, classes, start, end, sel
$classSet = [];
walkCssOffsets($body, $bodyStart, function ($selector, $absStart, $absEnd) use (&$blocks, &$classSet, $lineOf) {
    if (str_contains($selector, '\\')) {
        return; // selector ter-escape — jangan diutak-atik
    }
    preg_match_all('/\.([a-zA-Z_][a-zA-Z0-9_-]*)/', $selector, $classes);
    $classes = array_values(array_unique($classes[1]));
    if ($classes === []) {
        return;
    }
    foreach ($classes as $c) {
        $classSet[$c] = true;
    }
    $blocks[] = [
        'owner' => $classes[0],
        'classes' => $classes,
        'start' => $lineOf($absStart),
        'end' => $lineOf($absEnd),
        'sel' => $selector,
    ];
});

// ---------- tentukan class mati (owner-only, sama dengan audit) ----------
$usedClasses = [];
foreach ($blocks as $b) {
    foreach ($b['classes'] as $c) {
        if ($usedFn($c)) {
            $usedClasses[$c] = true;
        }
    }
}
$deadOwners = [];
foreach ($blocks as $b) {
    if (! isset($usedClasses[$b['owner']])) {
        $deadOwners[$b['owner']] = true;
    }
}
$deadClasses = array_keys($deadOwners);

// ---------- kumpulkan range baris yang akan dihapus (hanya blok owner-mati) ----------
$ranges = [];
foreach ($blocks as $b) {
    if (isset($deadOwners[$b['owner']])) {
        $ranges[] = ['start' => $b['start'], 'end' => $b['end'], 'class' => $b['owner'], 'sel' => $b['sel']];
    }
}
usort($ranges, fn ($a, $b) => $a['start'] <=> $b['start']);

// Merge range yang berdekatan/overlap (satu blok bisa punya beberapa class mati)
$merged = [];
foreach ($ranges as $r) {
    if ($merged !== [] && $r['start'] <= end($merged)['end'] + 1) {
        $last = array_pop($merged);
        $merged[] = [
            'start' => $last['start'],
            'end' => max($last['end'], $r['end']),
            'class' => $last['class'].' + '.$r['class'],
            'sel' => $last['sel'].' | '.$r['sel'],
        ];
    } else {
        $merged[] = $r;
    }
}

// ---------- ringkasan ----------
$lines = explode("\n", $css);
$delBytes = 0;
foreach ($merged as $r) {
    for ($ln = $r['start']; $ln <= $r['end']; $ln++) {
        $delBytes += strlen(($lines[$ln - 1] ?? '')."\n");
    }
}
echo 'Class mati: '.count($deadClasses).' | blok akan dihapus: '.count($merged).' | ~'.number_format($delBytes)." bytes (baris ".
    (count($merged) ? $merged[0]['start'].'-'.$merged[count($merged) - 1]['end'] : '-').")\n\n";
foreach ($merged as $r) {
    printf("  L%4d-%4d  %s\n        → %s\n", $r['start'], $r['end'], $r['class'], substr($r['sel'], 0, 90));
}

if (! $apply) {
    echo "\nDry-run — tidak ada yang ditulis. Jalankan dengan --apply untuk menghapus.\n";
    printf("⏱  %.2fs\n", microtime(true) - $t0);
    exit(0);
}

// ---------- GUARD: jangan hapus baris yang berbagi blok live ----------
// Baris yang akan dihapus:
$deleteLines = [];
foreach ($merged as $r) {
    for ($ln = $r['start']; $ln <= $r['end']; $ln++) {
        $deleteLines[$ln] = true;
    }
}
$deadSet = array_fill_keys($deadClasses, true);
foreach ($blocks as $b) {
    if (isset($deadSet[$b['owner']])) {
        continue; // blok yang akan dihapus
    }
    for ($ln = $b['start']; $ln <= $b['end']; $ln++) {
        if (isset($deleteLines[$ln])) {
            fwrite(STDERR, "ABORT: baris $ln dipakai blok live dan blok mati sekaligus — ".$b['sel']."\n");
            exit(1);
        }
    }
}

// ---------- apply: hapus baris (descending) + backup ----------
$lines = explode("\n", $css);
$bak = '/tmp/app.css.bak-'.date('Ymd-His');
file_put_contents($bak, $css);
echo "\nBackup: $bak\n";

// Hapus per range (descending supaya nomor baris tetap valid)
usort($merged, fn ($a, $b) => $b['start'] <=> $a['start']);
foreach ($merged as $r) {
    array_splice($lines, $r['start'] - 1, $r['end'] - $r['start'] + 1);
}
$new = implode("\n", $lines);
file_put_contents($cssPath, $new);

printf("DITULIS: app.css %.0f → %.0f bytes (-%s)\n", $cssLen, strlen($new), number_format($cssLen - strlen($new)));
printf("⏱  %.2fs\n", microtime(true) - $t0);
