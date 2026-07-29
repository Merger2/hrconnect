<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];

$tracked = [];
exec('git ls-files', $tracked, $exitCode);

if ($exitCode !== 0) {
    fwrite(STDERR, "Could not inspect tracked files with git ls-files.\n");
    exit(1);
}

foreach ($tracked as $path) {
    if (str_starts_with($path, 'secure_tools/')) {
        $failures[] = "Private enterprise tool is tracked: {$path}";
    }

    if (str_starts_with($path, 'enterprise_build/')) {
        $failures[] = "Generated enterprise build artifact is tracked: {$path}";
    }

    if (str_ends_with($path, '.Source.php')) {
        $failures[] = "Private enterprise source mirror is tracked: {$path}";
    }

    if ($path === 'scripts/build-enterprise.php') {
        $failures[] = 'Internal enterprise obfuscator must not be published as scripts/build-enterprise.php.';
    }
}

$requiredIgnorePatterns = [
    '/secure_tools/',
    '/enterprise_build/',
    '*.Source.php',
];

$gitignore = file_get_contents($root.'/.gitignore') ?: '';

foreach ($requiredIgnorePatterns as $pattern) {
    if (! str_contains($gitignore, $pattern)) {
        $failures[] = "Missing .gitignore enterprise boundary pattern: {$pattern}";
    }
}

$composer = json_decode((string) file_get_contents($root.'/composer.json'), true);
$classmapExcludes = $composer['autoload']['exclude-from-classmap'] ?? [];

if (! in_array('**/*.Source.php', $classmapExcludes, true)) {
    $failures[] = 'composer.json must exclude **/*.Source.php from optimized autoload classmaps.';
}

if (is_file($root.'/scripts/build-enterprise.php')) {
    $failures[] = 'scripts/build-enterprise.php exists; keep the obfuscator under ignored secure_tools/ instead.';
}

if ($failures !== []) {
    fwrite(STDERR, "Enterprise boundary check failed:\n");
    foreach ($failures as $failure) {
        fwrite(STDERR, "- {$failure}\n");
    }

    exit(1);
}

echo "Enterprise boundary check passed.\n";
