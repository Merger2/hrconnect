<?php

declare(strict_types=1);

/**
 * Backup ciphertext mentah PII (6 model CipherSweetEncrypted) ke /tmp/pii-backup.json.
 *
 * DILARANG decrypt — cukup dump kolom terenkripsi apa adanya (rollback jika key hilang).
 * Sinkronkan daftar field dengan configureCipherSweet() di tiap model.
 *
 * Usage: php scripts/backup-pii.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

// Field terenkripsi per model (wajib sinkron dgn configureCipherSweet)
$fields = [
    'Company' => ['npwp', 'phone'],
    'Employee' => ['phone', 'nik', 'npwp', 'bank_account_number', 'bank_account_holder', 'bank_account_name', 'emergency_contact_name', 'emergency_contact_phone', 'emergency_contact_relation', 'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'address_detail'],
    'Site' => ['phone', 'address'],
    'WorkFromHomeRequest' => ['location_address'],
    'FamilyDetail' => ['nik', 'phone', 'address'],
    'Branch' => ['address'],
];

$backup = [];
$total = 0;

foreach ($fields as $model => $encryptedFields) {
    $class = 'App\\Models\\'.$model;
    $table = (new $class)->getTable();
    $keyName = (new $class)->getKeyName();

    $backup[$model] = [];

    foreach (DB::table($table)->get() as $row) {
        $data = [];
        foreach ($encryptedFields as $field) {
            if (isset($row->{$field}) && $row->{$field} !== null) {
                $data[$field] = $row->{$field};
            }
        }
        $backup[$model][$row->{$keyName}] = $data;
    }

    $total += count($backup[$model]);
    echo $model.': '.count($backup[$model]).' rows'.PHP_EOL;
}

file_put_contents('/tmp/pii-backup.json', json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

echo 'TOTAL backup: '.$total.' rows -> /tmp/pii-backup.json'.PHP_EOL;
