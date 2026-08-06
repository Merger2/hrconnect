#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * check-color-tokens.php — TOKEN-ONLY RULE (design.md)
 *
 * Audit otomatis: pastikan TIDAK ADA warna hardcode (hex / rgb / rgba /
 * oklch / hsl) di seluruh source frontend, kecuali pengecualian teknis yang
 * terdokumentasi.
 *
 * Yang di-scan:
 *   1. resources/css/app.css        — warna non-netral di luar @theme
 *   2. resources/css/vendor/*.css   — semua warna non-netral (file ini
 *      custom proyek, bukan vendor murni — wajib token)
 *   3. resources/views (semua *.blade.php) — warna di luar whitelist pengecualian
 *   4. resources/js (semua *.js)    — warna non-netral
 *   5. app (semua *.php)            — warna hanya boleh di DesignTokens.php
 *
 * SCOPE (sengaja, UI token rule): database/, config/, tests/ TIDAK di-scan —
 * seeder yang menyimpan warna brand ke tabel settings tidak tercakup; itu
 * data, bukan desain frontend. Kalau suatu saat UI membaca warna dari DB,
 * audit ini harus diperluas.
 *
 * Pengecualian teknis (wajib literal, didokumentasikan di design.md):
 *   - admin/attendances/report.blade.php   (standalone print, tanpa @vite)
 *   - errors/minimal.blade.php             (standalone, token lokal sendiri)
 *   - <meta name="theme-color">            (browser tak resolve var() di meta)
 *   - SVG data-URI (url("data:image/svg+xml,…")) — string statis
 *   - rgb(0 0 0 / …)                       (shadow netral hitam, universal)
 *   - resources/views/vendor/**            (boilerplate framework)
 *   - app/Support/DesignTokens.php         (definisi token, bukan usage)
 *
 * Usage: php scripts/check-color-tokens.php
 * Exit:  0 = bersih, 1 = ada hardcode (untuk CI).
 */
$root = dirname(__DIR__);
$failures = [];
$scanned = ['css' => 0, 'blade' => 0, 'js' => 0, 'php' => 0];

// ─────────────────────────── helpers ───────────────────────────

function lineNumberFromOffset(string $content, int $offset): int
{
    return substr_count(substr($content, 0, $offset), "\n") + 1;
}

/** Teks baris yang memuat offset (untuk cek pengecualian per-baris). */
function lineTextFromOffset(string $content, int $offset): string
{
    $start = strrpos(substr($content, 0, $offset), "\n");

    $start = $start === false ? 0 : $start + 1;
    $end = strpos($content, "\n", $offset);

    if ($end === false) {
        $end = strlen($content);
    }

    return substr($content, $start, $end - $start);
}

/** Cek apakah baris adalah <meta name="theme-color"> (pengecualian resmi). */
function isThemeColorLine(string $line): bool
{
    return str_contains($line, 'theme-color');
}

/** Cek apakah hex berada di dalam url("data:…") (SVG data-URI — pengecualian). */
function isInsideDataUri(string $line, int $offset): bool
{
    $before = substr($line, 0, $offset);

    return str_contains($before, 'data:image/svg') || str_contains($before, 'data:image/svg+xml');
}

/** Warna netral hitam (shadow universal — pengecualian)? */
function isNeutralBlack(string $match): bool
{
    $lower = strtolower($match);

    if (preg_match('/rgb\(\s*0\s*0\s*0/i', $lower) === 1) {
        return true;
    }

    return preg_match('/rgba?\(\s*0\s*,\s*0\s*,\s*0/i', $lower) === 1;
}

/**
 * Strip komentar sebelum scan — mencegah false positive saat dev menulis
 * hex/oklch di komentar (mis. "/* was #f00 *​/"). CSS & JS: blok /* *​/;
 * Blade: {{-- --}} dan <!-- -->. Line comment JS (//) TIDAK di-strip karena
 * bisa merusak string URL (http://…) — blok comment cukup untuk kasus umum.
 */
function stripComments(string $content, string $kind): string
{
    if ($kind === 'blade') {
        $content = preg_replace('/\{\{--.*?--\}\}/s', '', $content) ?? $content;
        $content = preg_replace('/<!--.*?-->/s', '', $content) ?? $content;

        return $content;
    }

    // CSS & JS: blok komentar /* ... */ (non-greedy, DOTALL).
    return preg_replace('/\/\*.*?\*\//s', '', $content) ?? $content;
}

/** Kumpulkan temuan hex per baris (skip theme-color & data-URI). */
function scanHex(string $content, string $relativePath, array &$failures): void
{
    if (preg_match_all('/#[0-9a-fA-F]{3,8}\b/', $content, $matches, PREG_OFFSET_CAPTURE) === false) {
        return;
    }

    foreach ($matches[0] as [$hex, $offset]) {
        $lineText = lineTextFromOffset($content, $offset);

        if (isThemeColorLine($lineText)) {
            continue;
        }

        if (isInsideDataUri($lineText, $offset)) {
            continue;
        }

        $failures[] = sprintf('hex %s — %s:%d', strtolower($hex), $relativePath, lineNumberFromOffset($content, $offset));
    }
}

/** Kumpulkan temuan rgb/rgba/oklch/hsl/hsla non-netral. */
function scanRgb(string $content, string $relativePath, array &$failures): void
{
    // rgb/rgba (comma atau space syntax) + oklch + hsl/hsla.
    $pattern = '/(?:rgba?\(\s*\d{1,3}\s*[, ]\s*\d{1,3}\s*[, ]\s*\d{1,3})|(?:oklch\(\s*[\d.]+%?\s)|(?:hsla?\(\s*\d{1,3}\s)/i';

    if (preg_match_all($pattern, $content, $matches, PREG_OFFSET_CAPTURE) === false) {
        return;
    }

    foreach ($matches[0] as [$match, $offset]) {
        $lower = strtolower($match);

        if (isNeutralBlack($lower)) {
            continue;
        }

        $failures[] = sprintf('%s — %s:%d', $match, $relativePath, lineNumberFromOffset($content, $offset));
    }
}

function relativePath(string $root, string $path): string
{
    $root = rtrim(str_replace('\\', '/', $root), '/');
    $path = str_replace('\\', '/', $path);

    return ltrim(substr($path, strlen($root)), '/');
}

function findFiles(string $directory, callable $filter): array
{
    if (! is_dir($directory)) {
        return [];
    }

    $files = [];
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (! $file instanceof SplFileInfo || ! $file->isFile()) {
            continue;
        }

        $path = str_replace('\\', '/', $file->getPathname());

        if ($filter($path)) {
            $files[] = $path;
        }
    }

    sort($files);

    return $files;
}

// ───────────────────── 1. app.css (di luar @theme) ─────────────────────

$appCss = (string) file_get_contents($root.'/resources/css/app.css');

// Anchor robust: blok @theme yang di dalamnya ada deklarasi --color-*.
// (Bukan sekadar '@theme {' pertama — komentar yang menyebut "@theme {"
// sebelum blok asli tidak boleh menggeser anchor.)
if (preg_match('/@theme\s*\{(?:(?!\}).)*--color-[^}]*\}/s', $appCss, $themeMatch) === 1) {
    $content = substr($appCss, 0, strpos($appCss, $themeMatch[0]))
        .substr($appCss, strpos($appCss, $themeMatch[0]) + strlen($themeMatch[0]));
} else {
    fwrite(STDERR, "FAIL: blok @theme (dengan --color-*) tidak ditemukan di resources/css/app.css\n");

    exit(1);
}

$content = stripComments($content, 'css');
$scanned['css']++;
scanHex($content, 'resources/css/app.css', $failures);
scanRgb($content, 'resources/css/app.css', $failures);

// ───────────────────── 2. CSS lain (vendor/custom) ─────────────────────

foreach (findFiles($root.'/resources/css', static fn (string $p): bool => str_ends_with($p, '.css') && ! str_ends_with($p, '/app.css')) as $file) {
    $relative = relativePath($root, $file);
    $css = stripComments((string) file_get_contents($file), 'css');
    $scanned['css']++;
    scanHex($css, $relative, $failures);
    scanRgb($css, $relative, $failures);
}

// ───────────────────── 3. Blade (whitelist pengecualian) ─────────────────────

$bladeExceptions = [
    'resources/views/admin/attendances/report.blade.php',
    'resources/views/errors/minimal.blade.php',
];

foreach (findFiles($root.'/resources/views', static fn (string $p): bool => str_ends_with($p, '.blade.php')) as $file) {
    $relative = relativePath($root, $file);

    if (str_contains($relative, '/vendor/')) {
        continue; // boilerplate framework — bukan desain kita
    }

    if (in_array($relative, $bladeExceptions, true)) {
        continue; // standalone tanpa @vite — pengecualian terdokumentasi
    }

    $content = stripComments((string) file_get_contents($file), 'blade');
    $scanned['blade']++;
    scanHex($content, $relative, $failures);
    scanRgb($content, $relative, $failures); // gap fix 2026-08-06: rgb/rgba di blade juga wajib token
}

// ───────────────────── 4. JS ─────────────────────

foreach (findFiles($root.'/resources/js', static fn (string $p): bool => str_ends_with($p, '.js')) as $file) {
    $relative = relativePath($root, $file);
    $content = stripComments((string) file_get_contents($file), 'js');
    $scanned['js']++;
    scanHex($content, $relative, $failures);
    scanRgb($content, $relative, $failures);
}

// ───────────────────── 5. app/** PHP ─────────────────────

foreach (findFiles($root.'/app', static fn (string $p): bool => str_ends_with($p, '.php')) as $file) {
    $relative = relativePath($root, $file);

    if ($relative === 'app/Support/DesignTokens.php') {
        continue; // definisi token — pengecualian terdokumentasi
    }

    $content = stripComments((string) file_get_contents($file), 'js');
    $scanned['php']++;
    scanHex($content, $relative, $failures);
}

// ───────────────────── output ─────────────────────

$uniqueFailures = array_values(array_unique($failures));

echo 'Color Token Audit (token-only rule, design.md)'.PHP_EOL;
echo '=============================================='.PHP_EOL;
echo sprintf(
    'Scanned %d CSS, %d Blade, %d JS, %d PHP file(s).',
    $scanned['css'],
    $scanned['blade'],
    $scanned['js'],
    $scanned['php'],
).PHP_EOL;

if ($uniqueFailures === []) {
    echo 'PASS: zero hardcoded colors outside documented exceptions.'.PHP_EOL;

    exit(0);
}

echo PHP_EOL.'Hardcoded colors found ('.count($uniqueFailures).'):'.PHP_EOL;

foreach ($uniqueFailures as $failure) {
    echo '  - '.$failure.PHP_EOL;
}

echo PHP_EOL.'Fix: use design tokens (var(--color-*), color-mix, design_token() for PDF/email).'.PHP_EOL;
echo 'Exceptions documented in design.md (TOKEN-ONLY RULE section).'.PHP_EOL;

exit(1);
