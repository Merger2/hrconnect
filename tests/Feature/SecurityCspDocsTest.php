<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

beforeEach(function () {
    // RestrictedDocsAccess mengizinkan docs saat env=local ATAU gate viewApiDocs.
    // Env test = testing, jadi definisikan gate (param pertama opsional agar
    // dipanggil untuk guest) untuk mensimulasikan deployment yang mengaktifkan docs.
    Gate::define('viewApiDocs', fn ($user = null) => true);
});

it('allows unpkg CDN only on the Scramble docs route', function () {
    // /docs/api (Stoplight Elements UI) butuh unpkg untuk script+style
    $docs = $this->get('/docs/api');
    $docs->assertOk();
    $csp = $docs->headers->get('Content-Security-Policy') ?? '';
    expect($csp)->toContain('https://unpkg.com');

    // Halaman lain TIDAK boleh melebar ke unpkg
    $login = $this->get('/login');
    $login->assertOk();
    $loginCsp = $login->headers->get('Content-Security-Policy') ?? '';
    expect($loginCsp)->not->toContain('unpkg.com');
});
