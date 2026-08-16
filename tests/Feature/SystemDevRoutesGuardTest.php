<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\Sanctum;

/**
 * Guard route dev/e2e (routes/web/system.php): semua endpoint dev harus
 * abort(404) di luar APP_ENV local/testing — termasuk saat APP_DEBUG=true
 * (guard env-only, bukan env ATAU debug). Endpoint: __e2e-login,
 * __e2e-document-upload, test-error/{code}, __auth-debug, _boost/browser-logs.
 */
it('mengembalikan 404 untuk semua route dev/e2e saat APP_ENV production', function () {
    // Auth middleware (auth:sanctum + verified) di __auth-debug dan
    // __e2e-document-upload harus LULUS dulu supaya guard env controller
    // yang benar-benar diuji — bukan middleware yang menolak.
    Sanctum::actingAs(User::factory()->create());

    app()->detectEnvironment(fn () => 'production');
    // POST di bawah butuh bypass CSRF (runningUnitTests mati saat env diganti
    // ke production). Endpoint _boost/browser-logs memang CSRF-exempt
    // (bootstrap/app.php), __e2e-document-upload tidak.
    $this->withoutMiddleware(PreventRequestForgery::class);

    try {
        $this->get('/__e2e-login')->assertNotFound();
        $this->get('/test-error/500')->assertNotFound();
        $this->get('/__auth-debug')->assertNotFound();
        $this->post('/_boost/browser-logs')->assertNotFound();
        $this->post('/__e2e-document-upload')->assertNotFound();
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }
});

it('tetap 404 untuk route dev/e2e di production meski APP_DEBUG=true', function () {
    config()->set('app.debug', true);
    Sanctum::actingAs(User::factory()->create());

    app()->detectEnvironment(fn () => 'production');
    $this->withoutMiddleware(PreventRequestForgery::class);

    try {
        $this->get('/__e2e-login')->assertNotFound();
        $this->get('/test-error/500')->assertNotFound();
        $this->get('/__auth-debug')->assertNotFound();
        $this->post('/_boost/browser-logs')->assertNotFound();
        $this->post('/__e2e-document-upload')->assertNotFound();
    } finally {
        app()->detectEnvironment(fn () => 'testing');
    }
});

it('route dev/e2e tetap berfungsi di environment testing', function () {
    $this->post('/_boost/browser-logs')->assertOk();

    $this->get('/test-error/500')->assertStatus(500);

    $user = User::factory()->create();

    // __e2e-login memanggil Auth::login — harus pakai guard session (web),
    // bukan sanctum (stateless) yang bisa tersisa dari test lain / actingAs.
    Auth::shouldUse('web');

    $query = http_build_query([
        'token' => 'local-apk-e2e',
        'email' => $user->email,
        'to' => '/home',
    ]);
    $this->get('/__e2e-login?'.$query)->assertRedirect('/home');

    Sanctum::actingAs($user);
    $this->get('/__auth-debug')->assertOk();
});
