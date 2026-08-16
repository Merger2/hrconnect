<?php

namespace Tests\Feature\Database;

use App\Models\EmployeeDocumentTemplate;
use App\Models\EmployeeDocumentType;
use Database\Seeders\EmployeeDocumentTemplateSeeder;
use Illuminate\Support\Facades\Artisan;

test('seed runs without error if tables exist', function () {
    // simulate missing or invalid enterprise obfuscator key
    config(['enterprise.obfuscator_key' => 'bogus-key']);

    Artisan::call('db:seed', ['--class' => EmployeeDocumentTemplateSeeder::class]);

    // Verify that the types were created despite no valid enterprise key
    expect(EmployeeDocumentType::query()->count())->toBeGreaterThan(0);

    // Verify that the templates were also created
    expect(EmployeeDocumentTemplate::query()->count())->toBeGreaterThan(0);
});
