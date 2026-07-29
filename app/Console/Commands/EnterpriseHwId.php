<?php

namespace App\Console\Commands;

/**
 * Generate a deterministic hardware ID from app key, DB name, and hostname.
 *
 * Usage (Blade):
 *   {{ \App\Console\Commands\EnterpriseHwId::generate() }}
 */
class EnterpriseHwId
{
    /**
     * Generate a deterministic HWID string.
     *
     * Combines the APP_KEY hash, database name, and hostname into a
     * stable, reversible-only-by-us fingerprint that survives deploys
     * but differs between environments.
     */
    public static function generate(): string
    {
        $appKey = config('app.key');
        $dbName = config('database.connections.'.config('database.default').'.database', 'unknown');
        $hostname = gethostname() ?: php_uname('n');

        $raw = sprintf('%s|%s|%s', $appKey, $dbName, $hostname);

        return strtoupper(substr(sha1($raw), 0, 16));
    }
}
