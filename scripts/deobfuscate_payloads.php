<?php

// Deobfuscator: save the original payload without execution

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

    // Extract the full base64 string using regex, accounting for +/ and = pad characters
    if (preg_match("/base64_decode\('([A-Za-z0-9+\\/=]+)'\)/", $content, $m)) {
        $payload = $m[1];

        // Save the payload in a .txt file
        $payloadFile = $full.'.payload_base64.txt';
        file_put_contents($payloadFile, $payload);
        echo "PAYLOAD SAVED: $payloadFile (len: ".strlen($payload).")\n";

        // Try to decode (but don't run) for a preview of result size
        $decoded = gzinflate(base64_decode($payload)) ? true : false;
        echo '  Decode result: '.($decoded ? 'SUCCESS (preview)' : 'FAILED')."\n";
    } else {
        echo "NO PAYLOAD: $target\n";
    }
}
