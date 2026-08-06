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

        // Remove INLINE @json(...) / @js(...) directives (balanced-paren scan).
        // These render to a JS value at runtime, so `0` keeps the line valid.
        $js = stripInlineJsonDirectives($js);

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

/**
 * Replace inline @json(...) / @js(...) directives with `0`.
 *
 * Blade renders these to a JS value at runtime (a JSON literal), so `0`
 * keeps the surrounding statement syntactically valid for `node --check`.
 * A balanced-paren scan handles nested parentheses in the argument
 * (e.g. @json((bool) $x) or @json(__('Required'))).
 */
function stripInlineJsonDirectives(string $js): string
{
    $out = '';
    $i = 0;
    $len = strlen($js);

    while ($i < $len) {
        $jsonPos = strpos($js, '@json(', $i);
        $jsPos = strpos($js, '@js(', $i);
        $candidates = array_filter([$jsonPos, $jsPos], fn ($p) => $p !== false);
        $start = $candidates ? min($candidates) : false;

        if ($start === false) {
            $out .= substr($js, $i);

            break;
        }

        $out .= substr($js, $i, $start - $i);

        // Position right after the opening paren of the matched directive.
        $afterOpen = $start + ($jsonPos === $start ? strlen('@json(') : strlen('@js('));

        // Balanced-paren scan for the matching close paren.
        $depth = 0;
        $close = -1;

        for ($j = $afterOpen; $j < $len; $j++) {
            if ($js[$j] === '(') {
                $depth++;
            } elseif ($js[$j] === ')') {
                if ($depth === 0) {
                    $close = $j;

                    break;
                }
                $depth--;
            }
        }

        if ($close === -1) {
            // Unbalanced — leave the rest untouched rather than corrupting it.
            $out .= substr($js, $start);

            break;
        }

        $out .= '0';
        $i = $close + 1;
    }

    return $out;
}
