<?php

// Deobfuscator: extract original source from eval(gzinflate(base64_decode('...')))

$targets = [
    'app/Livewire/Finance/Concerns/ManagesCashAdvances.php',
    'app/Livewire/User/Finance/TeamCashAdvanceManager.php',
];

foreach ($targets as $target) {
    $full = __DIR__.'/../'.$target;
    if (! file_exists($full)) {
        echo "SKIP (not found): $target\n";

        continue;
    }

    $content = file_get_contents($full);

    // Extract the base64 payload inside base64_decode('...')
    if (! preg_match("/base64_decode\('([^']+)'\)/", $content, $m)) {
        echo "NO PAYLOAD: $target\n";

        continue;
    }

    $payload = $m[1];
    $decoded = gzinflate(base64_decode($payload));

    if ($decoded === false) {
        echo "DECODE FAILED: $target\n";

        continue;
    }

    // Some payloads are themselves nested. Recurse if we still see eval(gzinflate.
    $depth = 0;
    while (preg_match("/eval\(gzinflate\(base64_decode\('([^']+)'\)\)\)/", $decoded, $mm) && $depth < 10) {
        $inner = gzinflate(base64_decode($mm[1]));
        if ($inner === false) {
            break;
        }
        $decoded = $inner;
        $depth++;
    }

    // Ensure it starts with <?php
    if (strpos($decoded, '<?php') === false) {
        $decoded = "<?php\n".$decoded;
    }

    echo "=== DECODED: $target (nested depth: $depth) ===\n";
    echo substr($decoded, 0, 400)."\n...\n\n";

    // Write to .decoded file for review first (don't overwrite yet)
    file_put_contents($full.'.decoded', $decoded);
    echo "Written: {$target}.decoded (".strlen($decoded)." bytes)\n\n";
}
