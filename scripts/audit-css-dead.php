<?php

/**
 * Audit CSS class mati — HRConnect (2026-08-08).
 *
 * Scan app.css: untuk setiap blok selector di @layer components & @layer utilities,
 * cek apakah nama class-nya muncul di resources/views (blade) atau resources/js.
 * Output: daftar class dengan 0 referensi + estimasi ukuran (byte), plus analisis
 * pemakaian per-halaman (untuk menilai kelayakan split per halaman).
 *
 * AKURASI (baca sebelum menghapus):
 * - Satu blok dianggap "terpakai" jika SETIDAKNYA SATU class di selector-nya
 *   direferensikan di blade/js. Class owner (class pertama) hanya masuk daftar
 *   "mati" jika SEMUA blok yang ia own punya 0 referensi. Arah ini sengaja
 *   konservatif (menghindari false-positive "mati" yang bisa menghapus CSS terpakai).
 * - Referensi dicek dengan tokenisasi identifier (bukan substring liar): class
 *   BEM `card__header` tidak dianggap merujuk `.card`. Prefix per segmen hyphen
 *   ditambahkan (module-hr → module + module-hr) untuk menangkap class yang
 *   dibangun dinamis.
 * - Class yang dibangun dinamis (mis. "module-".$x, x-bind:class) tetap perlu
 *   review manual — lihat kolom "dinamis?" (selector mengandung $ / { }).
 *
 * Usage: php scripts/audit-css-dead.php [--min-bytes=100] [--pages-top=15] [--verbose]
 */

$t0 = microtime(true);
// Tool audit satu-off: butuh >128MB default karena menyimpan ribuan blok CSS + token.
ini_set('memory_limit', '512M');
$minBytes = 100;
$pagesTop = 15;
$verbose = false;
foreach ($argv as $i => $a) {
    if (str_starts_with($a, '--min-bytes=')) {
        $minBytes = (int) substr($a, strlen('--min-bytes='));
    }
    if (str_starts_with($a, '--pages-top=')) {
        $pagesTop = (int) substr($a, strlen('--pages-top='));
    }
    if ($a === '--verbose') {
        $verbose = true;
    }
}

$root = dirname(__DIR__);
$css = file_get_contents($root.'/resources/css/app.css');
$cssLen = strlen($css);
echo 'app.css: '.number_format($cssLen)." bytes (".substr_count($css, "\n")." baris)\n";

// Kumpulkan semua teks blade + js + vendor @source (sesuai deklarasi @source di app.css)
// untuk pencarian referensi — supaya class yang dipakai hanya di vendor blade
// (jetstream, pagination) tidak salah dianggap mati.
$haystack = '';
$allBladeFiles = [];
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
            if (str_ends_with($f->getFilename(), '.blade.php') && str_starts_with($f->getPathname(), $root.'/resources/views')) {
                $allBladeFiles[] = $f->getPathname();
            }
        }
    }
}
echo 'Scanned: '.number_format(strlen($haystack))." bytes blade+js+vendor (".count($allBladeFiles)." blade files)\n";

// Tokenisasi haystack per-chunk (hemat memori — satu preg_match_all penuh
// menyimpan ratusan ribu match string sekaligus): identifier + prefix per segmen hyphen.
// $prefixes: token yang berakhiran '-' (mis. "module-" dari `module-${x}`, atau
// "attendance-panel__step--{{ $state }}") menandakan class dibangun dinamis —
// class CSS yang DIAWALI token tsb dianggap terpakai.
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

// $usedFn: true jika class direferensikan eksplisit ATAU dicakup prefix dinamis.
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
echo 'Token identifier unik: '.number_format(count($tokens)).' | prefix dinamis: '.count($prefixes)."\n";

/**
 * Parser CSS depth-aware.
 * Memanggil $cb($selector, $body) untuk setiap rule block (selector bukan @-rule),
 * dan merekursi ke dalam body at-rule (@media/@supports/@keyframes/dst).
 * Tahan komentar /* *\/ dan string '...' / "..." (brace di dalamnya tidak dihitung).
 */
function walkCss(string $css, callable $cb): void
{
    $len = strlen($css);
    $depth = 0;
    $selStart = -1;
    $blockStart = 0;
    $quote = null;
    $i = 0;
    while ($i < $len) {
        $ch = $css[$i];

        if ($ch === '/' && ($css[$i + 1] ?? '') === '*') { // komentar
            $end = strpos($css, '*/', $i + 2);
            $i = ($end === false) ? $len : $end + 2;
            continue;
        }
        if ($quote !== null) { // di dalam string
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
            $sel = trim(preg_replace('/\s+/', ' ', substr($css, max(0, $selStart), $i - max(0, $selStart))));
            if ($depth === 0) {
                $blockStart = $i + 1;
            }
            $depth++;
            $selStart = -1;
            $i++;
            continue;
        }
        if ($ch === '}') {
            if ($depth === 1) {
                $body = substr($css, $blockStart, $i - $blockStart);
                if ($sel !== '') {
                    if (str_starts_with($sel, '@')) {
                        walkCss($body, $cb); // at-rule: rekursi
                    } else {
                        $cb($sel, $body);
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

// Ekstrak bagian @layer components dan @layer utilities
$layerSections = [];
if (preg_match('/@layer components\s*\{(.*?)\}\s*@layer utilities/s', $css, $mComp)) {
    $layerSections['components'] = $mComp[1];
}
if (preg_match('/@layer utilities\s*\{(.*?)\}\s*$/s', $css, $mUtil)) {
    $layerSections['utilities'] = $mUtil[1];
}

$stats = ['components' => ['bytes' => 0, 'blocks' => 0], 'utilities' => ['bytes' => 0, 'blocks' => 0]];
$blocks = []; // daftar blok: owner, classes, bytes, layer, selector
$classSet = [];

// Analisis class mati HANYA di @layer components.
// @layer utilities di-skip: isinya utilities Tailwind hasil kompilasi (mis.
// .rounded-b-lg, .odd\\:bg-white\\/2) — Tailwind v4 hanya meng-emit utilities yang
// terpakai, jadi semuanya pasti hidup; class ter-escape (\\) juga tidak bisa
// di-parsing owner-nya dengan benar.
foreach ($layerSections as $layer => $body) {
    $stats[$layer]['bytes'] = strlen($body);
    if ($layer !== 'components') {
        continue;
    }
    walkCss($body, function ($selector, $block) use ($layer, &$stats, &$blocks, &$classSet) {
        $stats[$layer]['blocks']++;
        if (str_contains($selector, '\\')) {
            return; // selector ter-escape (utilities/variant) — bukan BEM handwritten
        }
        preg_match_all('/\.([a-zA-Z_][a-zA-Z0-9_-]*)/', $selector, $classes);
        $classes = array_values(array_unique($classes[1]));
        if ($classes === []) {
            return; // selector tanpa class (body, html, [attr], dsb)
        }
        $blockBytes = strlen($selector) + strlen($block) + 2;
        $blocks[] = [
            'owner' => $classes[0],
            'classes' => $classes,
            'bytes' => $blockBytes,
            'layer' => $layer,
            'selector' => $selector,
            'dynamic' => preg_match('/\b(module|hue|tab|status|variant|color|size|state)-\$|:\$|\$[a-z]|\$\{/', $selector) ? true : false,
        ];
        unset($block); // jangan simpan body — hanya butuh panjangnya
        foreach ($classes as $c) {
            $classSet[$c] = true;
        }
    });
}

// Putuskan: class owner mati hanya jika SEMUA bloknya punya 0 referensi.
$usedClasses = []; // class yang terbukti terpakai
$ownerBlocks = []; // owner => [index blok]
foreach ($blocks as $idx => $b) {
    $ownerBlocks[$b['owner']][] = $idx;
    foreach ($b['classes'] as $c) {
        if ($usedFn($c)) {
            $usedClasses[$c] = true;
        }
    }
}

$dead = []; // class mati => info
$usedCount = 0;
foreach ($classSet as $c => $_) {
    if (isset($usedClasses[$c])) {
        $usedCount++;
        continue;
    }
    // Semua class di sini 0-referensi; tapi block milik owner ini tetap "mati" hanya
    // jika tidak ada class lain di selector-nya yang terpakai (sudah dijamin karena
    // usedClasses kosong utk semua class-nya).
    $bytes = 0;
    $sel = '';
    $layer = '';
    $dynamic = false;
    foreach ($ownerBlocks[$c] ?? [] as $idx) {
        $b = $blocks[$idx];
        $bytes += $b['bytes'];
        $sel = $b['selector'];
        $layer = $b['layer'];
        $dynamic = $dynamic || $b['dynamic'];
    }
    if ($bytes === 0) {
        continue; // class didefinisikan tapi tidak pernah jadi owner blok — abaikan
    }
    $dead[$c] = [
        'layer' => $layer,
        'bytes' => $bytes,
        'selector' => $sel,
        'dynamic' => $dynamic,
        'blocks' => count($ownerBlocks[$c]),
    ];
}

uasort($dead, fn ($a, $b) => $b['bytes'] <=> $a['bytes']);

$sureDead = array_filter($dead, fn ($d) => ! $d['dynamic']);
$sureBytes = array_sum(array_column($sureDead, 'bytes'));
$dynBytes = array_sum(array_map(fn ($d) => $d['bytes'], $dead)) - $sureBytes;

echo "\n=== HASIL ===\n";
echo 'Blok komponen: '.number_format($stats['components']['blocks']).' ('.number_format($stats['components']['bytes'])." bytes)\n";
echo 'Blok utilities: '.number_format($stats['utilities']['blocks']).' ('.number_format($stats['utilities']['bytes'])." bytes)\n\n";

echo 'Class unik didefinisikan: '.number_format(count($classSet))."\n";
echo '  - terbukti terpakai (token di blade/js): '.number_format($usedCount)."\n";
echo '  - 0 referensi: '.number_format(count($dead))."\n";
echo '      yakin mati (non-dinamis): '.number_format(count($sureDead)).' → ~'.number_format($sureBytes)." bytes\n";
echo '      pola dinamis (perlu review): '.number_format(count($dead) - count($sureDead)).' → ~'.number_format($dynBytes)." bytes\n\n";

$shown = 0;
foreach ($dead as $cls => $info) {
    if ($info['bytes'] < $minBytes) {
        continue;
    }
    $shown++;
    $flag = $info['dynamic'] ? '[DINAMIS?]' : '[MATI]';
    echo sprintf(
        "%-11s %8d B  %2dx  %-10s %s\n        → %s\n",
        $flag,
        $info['bytes'],
        $info['blocks'],
        $info['layer'],
        $cls,
        substr($info['selector'], 0, 110)
    );
}
echo "\n(menampilkan ".$shown." blok ≥ ".number_format($minBytes)." bytes dari ".count($dead)." total 0-ref)\n";

// Ringkasan pengurangan potensial
$pct = $sureBytes / max(1, $cssLen) * 100;
echo "\n=== ESTIMASI PENGURANGAN ===\n";
echo 'Hapus class yakin mati (non-dinamis): -'.number_format($sureBytes)." bytes (-{$pct}% dari app.css)\n";
$withDyn = $sureBytes + $dynBytes;
echo 'Hapus semua 0-ref (termasuk dinamis setelah review): -'.number_format($withDyn).' bytes (-'.number_format($withDyn / max(1, $cssLen) * 100, 1)."%)\n";
echo "Catatan: bundle akhir (651KB) = app.css + utilities Tailwind ter-pakai + @theme; pengurangan di atas adalah pada sumber, bukan total bundle.\n";

// === Analisis per halaman ===
if ($pagesTop > 0) {
    echo "\n=== PEMAKAIAN PER HALAMAN (custom class @layer) ===\n";
    // Beban per class owner = total bytes semua blok miliknya (untuk analisis
    // beban per halaman — bukan hanya class mati).
    $classBytes = [];
    foreach ($blocks as $b) {
        $classBytes[$b['owner']] = ($classBytes[$b['owner']] ?? 0) + $b['bytes'];
    }
    $pageUsage = [];
    foreach ($allBladeFiles as $f) {
        $content = file_get_contents($f);
        $rel = str_replace($root.'/', '', $f);
        $bytes = 0;
        $found = [];
        preg_match_all('/class="([^"]+)"/', $content, $m);
        foreach ($m[1] as $val) {
            foreach (preg_split('/\s+/', trim($val)) as $tok) {
                if ($tok !== '' && isset($classBytes[$tok]) && ! isset($found[$tok])) {
                    $found[$tok] = true;
                    $bytes += $classBytes[$tok];
                }
            }
        }
        $pageUsage[$rel] = ['bytes' => $bytes, 'classes' => count($found)];
    }
    arsort($pageUsage);
    echo 'Halaman dengan beban custom-class terbesar (kandidat split):'."\n";
    $n = 0;
    foreach ($pageUsage as $rel => $u) {
        if ($u['classes'] === 0) {
            continue;
        }
        $n++;
        if ($n > $pagesTop) {
            break;
        }
        echo sprintf("  %6d B  %3d class  %s\n", $u['bytes'], $u['classes'], $rel);
    }
    echo "\nTotal: ".count($pageUsage)." blade punya custom class; ".$pagesTop." terbesar di atas.\n";
    echo "Interpretasi: jika 1-3 halaman memegang sebagian besar bytes (>40%), split per halaman layak;\n";
    echo "jika tersebar merata, lebih efektif menghapus class mati + mengecilkan @theme.\n";
}

printf("\n⏱  %.2fs\n", microtime(true) - $t0);
