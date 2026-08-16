<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Test Bootstrap
|--------------------------------------------------------------------------
|
| Laravel's Env::get() reads $_SERVER before $_ENV/getenv. PHPUnit <env>
| entries (even with force="true") only update putenv()/$_ENV, never
| $_SERVER — so any host environment variable (e.g. APP_ENV=local or
| DB_DATABASE exported by the developer shell) silently wins over
| phpunit.xml, leaving the suite running against the DEV database with
| CSRF enabled (every POST returns 419).
|
| This bootstrap forces the canonical testing values into all three
| sources before Laravel boots, regardless of PHPUnit's processing order.
|
*/

$testEnv = [
    'APP_ENV' => 'testing',
    'APP_MAINTENANCE_DRIVER' => 'file',
    'BCRYPT_ROUNDS' => '4',
    'BROADCAST_CONNECTION' => 'null',
    'CACHE_STORE' => 'array',
    'DB_CONNECTION' => 'pgsql',
    'DB_DATABASE' => 'hris_testing',
    'DB_USERNAME' => 'postgres',
    'DB_PASSWORD' => 'password',
    'MAIL_MAILER' => 'array',
    'QUEUE_CONNECTION' => 'sync',
    'SESSION_DRIVER' => 'array',
    'PULSE_ENABLED' => 'false',
    'TELESCOPE_ENABLED' => 'false',
    'NIGHTWATCH_ENABLED' => 'false',
    'CIPHERSWEET_KEY' => '0123456789abcdef0123456789abcdef',
];

foreach ($testEnv as $key => $value) {
    putenv("{$key}={$value}");
    $_ENV[$key] = $value;
    $_SERVER[$key] = $value;
}

require __DIR__.'/../vendor/autoload.php';
