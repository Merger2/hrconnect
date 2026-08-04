<?php

/**
 * Smoke-verify /scan renders after login, without 500s, and that the
 * fixed $wire.$watch pattern is present while the old this.$watch('$wire...') is gone.
 *
 * Usage: php scripts/verify-scan-page.php
 */
$base = 'http://localhost:8000';
$cookieJar = tempnam(sys_get_temp_dir(), 'cj-');

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieJar,
    CURLOPT_COOKIEFILE => $cookieJar,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_TIMEOUT => 20,
]);

// 1. GET /login
curl_setopt($ch, CURLOPT_URL, $base.'/login');
$loginHtml = curl_exec($ch);
$loginCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "GET /login            -> HTTP {$loginCode}\n";

if (! preg_match('/name="_token" value="([^"]+)"/', $loginHtml, $m)) {
    echo "ERROR: CSRF token not found on /login\n";
    exit(2);
}
$token = $m[1];

// 2. POST /login
curl_setopt_array($ch, [
    CURLOPT_URL => $base.'/login',
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        '_token' => $token,
        'email' => 'employee1@hrconnect.local',
        'password' => 'password',
    ]),
]);
curl_exec($ch);
$postCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
echo "POST /login           -> HTTP {$postCode}\n";

// 3. GET /scan (follow redirects to detect login failure redirects)
curl_setopt_array($ch, [
    CURLOPT_URL => $base.'/scan',
    CURLOPT_POST => false,
    CURLOPT_FOLLOWLOCATION => true,
]);
$scanHtml = curl_exec($ch);
$scanCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
echo "GET /scan             -> HTTP {$scanCode} (final URL: {$finalUrl})\n";

// 4. Assertions
$checks = [
    'page is /scan (not redirected to login)' => str_contains($finalUrl, '/scan'),
    'no 500/Whoops error page' => ! str_contains($scanHtml, 'Whoops, something went wrong'),
    'clock-in UI renders (Check In button)' => str_contains($scanHtml, 'Check In'),
    'location-card component renders' => (str_contains($scanHtml, 'locationCard') || str_contains($scanHtml, 'location-card')),
    'fixed pattern this.$wire.$watch present' => str_contains($scanHtml, '$wire.$watch'),
    'old pattern this.$watch(\'$wire removed' => ! str_contains($scanHtml, "this.\$watch('\$wire"),
];

$allPass = true;
foreach ($checks as $label => $pass) {
    echo ($pass ? '  PASS' : '  FAIL')."  {$label}\n";
    if (! $pass) {
        $allPass = false;
    }
}

unlink($cookieJar);
exit($allPass ? 0 : 1);
