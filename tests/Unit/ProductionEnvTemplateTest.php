<?php

use Tests\TestCase;

uses(TestCase::class);

test('production environment template enforces required deployment safeguards', function () {
    $templatePath = base_path('.env.production.example');

    expect($templatePath)->toBeFile();

    $template = file_get_contents($templatePath);

    expect($template)
        ->toContain('APP_ENV=production')
        ->toContain('APP_DEBUG=false')
        ->toContain('SESSION_SECURE_COOKIE=true')
        ->toContain('QUEUE_CONNECTION=database')
        ->toContain('SCHEDULE_QUEUE_WORKER=false')
        ->toContain('CACHE_STORE=database')
        ->toContain('MAIL_MAILER=smtp')
        ->toContain('CIPHERSWEET_KEY=GANTI_64CHAR_HEX')
        ->toContain('GEMINI_EMBEDDING_DIMENSIONS=768');
});
