<?php

/**
 * Quick JS syntax check for inline <script> blocks inside Blade files.
 *
 * Usage: php scripts/check-blade-js-syntax.php <file.blade.php> [more.blade.php ...]
 *
 * Strips Blade echo tags ({{ }}, {!! !!}) and @directives, then runs
 * `node --check` against each extracted script. Exits non-zero on any failure.
 */
$files = array_slice($argv, 1);

if (! $files) {
    fwrite(STDERR, "Usage: php scripts/check-blade-js-syntax.php <blade-file> [...]\n");
    exit(1);
}

$failed = false;

foreach ($files as $file) {
    if (! is_file($file)) {
        fwrite(STDERR, "SKIP (not found): {$file}\n");

        continue;
    }

    $content = file_get_contents($file);

    if (! preg_match_all('/<script[^>]*>(.*?)<\/script>/s', $content, $matches)) {
        echo "SKIP (no <script> blocks): {$file}\n";

        continue;
    }

    $index = 0;
    foreach ($matches[1] as $rawScript) {
        $index++;
        $js = $rawScript;

        // Remove Blade echo tags: {{ ... }} and {!! ... !!} (multiline safe).
        // Replace with `0` so inline `'{{ __(\'x\') }}'` stays a valid string literal.
        $js = preg_replace('/\{\{.*?\}\}/s', '0', $js);
        $js = preg_replace('/\{!!.*?!!\}/s', '0', $js);

        // Remove whole-line Blade directives (@push / @endpush / @php ... @endphp etc.)
        $js = preg_replace('/^\s*@\w+(\(.*?\))?\s*$/m', '', $js);

        $tmp = tempnam(sys_get_temp_dir(), 'bladejs-');
        file_put_contents($tmp, $js);

        exec('node --check '.escapeshellarg($tmp).' 2>&1', $output, $exitCode);
        unlink($tmp);

        if ($exitCode !== 0) {
            $failed = true;
            echo "FAIL: {$file} (script #{$index})\n";
            echo implode("\n", $output)."\n";
        } else {
            echo "OK:   {$file} (script #{$index}, ".strlen($js)." bytes)\n";
        }
    }
}

exit($failed ? 1 : 0);
