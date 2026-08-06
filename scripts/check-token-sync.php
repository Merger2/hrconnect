<?php

use App\Support\DesignTokens;
use Illuminate\Contracts\Console\Kernel;

/**
 * check-token-sync.php — TOKEN-ONLY RULE (design.md)
 *
 * Validasi tiga hal:
 *   1. CORE tokens di App\Support\DesignTokens sinkron dengan @theme di
 *      resources/css/app.css (key yang overlap harus identik).
 *   2. Arah sebaliknya: setiap warna di @theme punya mirror di DesignTokens
 *      (kecuali docs family — brand-green-*, muted-green-*, status-*,
 *      warning-*, brand-deep — yang khusus PDF/email, tidak perlu di @theme).
 *   3. Semua key `design_token('...')` / `design_rgba('...')` yang dipakai
 *      blade pdf/** + emails/** + documents/** HARUS ada di map — typo token
 *      jadi kegagalan CI, bukan warna salah diam-diam (no silent failure).
 *
 * Usage: php scripts/check-token-sync.php
 * Exit:  0 = semua sinkron, 1 = ada masalah.
 */
$root = dirname(__DIR__);
$appCss = file_get_contents($root.'/resources/css/app.css');

if (! preg_match('/@theme\s*\{(.*?)\}/s', $appCss, $match)) {
    fwrite(STDERR, "FAIL: blok @theme tidak ditemukan di resources/css/app.css\n");

    exit(1);
}

preg_match_all('/--color-([\w-]+):\s*([^;]+);/', $match[1], $pairs, PREG_SET_ORDER);

$theme = [];
foreach ($pairs as $pair) {
    $theme[$pair[1]] = strtolower(trim($pair[2]));
}

require $root.'/vendor/autoload.php';

$app = require $root.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$phpTokens = DesignTokens::all();

$problems = [];

// ── 1+2. Overlap dua arah ──
foreach ($phpTokens as $key => $value) {
    if (! array_key_exists($key, $theme)) {
        continue;
    }

    if (strtolower(trim($value)) !== $theme[$key]) {
        $problems[] = sprintf('nilai beda: %s → @theme=%s, DesignTokens=%s', $key, $theme[$key], strtolower(trim($value)));
    }
}

/**
 * @theme boleh punya var warna yang TIDAK ada di DesignTokens untuk dua
 * kelompok terdokumentasi (design.md — TOKEN-ONLY RULE):
 *
 *  1. Palet default Tailwind v4 (red/orange/amber/yellow/lime/green/emerald/
 *     teal/cyan/sky/blue/indigo/violet/purple/fuchsia/pink/rose/slate/gray/
 *     zinc/neutral/stone + white/black) — framework-provided, bukan desain
 *     kita. Di-rekonstruksi (2026-08-06) @theme ini memuatnya eksplisit
 *     supaya var tetap ter-emit walau pemakaian via @apply sudah ter-kompilasi.
 *  2. Token recovery `rec-*` — artefak rekonstruksi bundle 2026-08-06 (warna
 *     satu-off halaman scanner/native yang tidak terpetakan ke token desain).
 */
$defaultPalette = '/^(red|orange|amber|yellow|lime|green|emerald|teal|cyan|sky|blue|indigo|violet|purple|fuchsia|pink|rose|slate|gray|zinc|neutral|stone|white|black)(-\d+)?$/i';

foreach ($theme as $key => $value) {
    if (array_key_exists($key, $phpTokens)) {
        continue;
    }

    if (str_starts_with($key, 'rec-')) {
        continue; // recovery token (terdokumentasi di design.md)
    }

    if (preg_match($defaultPalette, $key)) {
        continue; // palet default Tailwind — bukan desain custom
    }

    $problems[] = sprintf('@theme punya token yang TIDAK ada di DesignTokens: %s (%s)', $key, $value);
}

// ── 3. Key blade pdf/email/docs harus ada di map ──
$bladeFiles = array_merge(
    glob($root.'/resources/views/pdf/*.blade.php') ?: [],
    glob($root.'/resources/views/emails/**/*.blade.php') ?: [],
    glob($root.'/resources/views/documents/*.blade.php') ?: [],
);

$usedKeys = [];
foreach ($bladeFiles as $file) {
    $content = file_get_contents($file);
    preg_match_all("/design_(?:token|rgba)\('([^']+)'/", $content, $matches);

    foreach ($matches[1] as $key) {
        $usedKeys[$key] = true;
    }
}

foreach (array_keys($usedKeys) as $key) {
    if (! array_key_exists($key, $phpTokens)) {
        $problems[] = sprintf('blade pakai token yang TIDAK ada di DesignTokens: "%s"', $key);
    }
}

if ($problems) {
    fwrite(STDERR, 'FAIL ('.count($problems).' issue):'.PHP_EOL);

    foreach ($problems as $problem) {
        fwrite(STDERR, '  - '.$problem.PHP_EOL);
    }

    exit(1);
}

printf(
    "OK: %d CORE overlap sinkron, %d key @theme ter-mirror, %d key blade terpakai valid.\n",
    count(array_intersect_key($phpTokens, $theme)),
    count($theme),
    count($usedKeys),
);

exit(0);
