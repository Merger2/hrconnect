<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Routes API HRConnect dengan prefix /api/v1 (lihat bootstrap/app.php).
| Authentication: Sanctum Bearer token untuk PWA mobile, cookie stateful
| untuk SPA same-origin.
|
| Token tidak expire (config sanctum.expiration = null) — PWA reuse
| sampai user logout manual atau admin revoke.
|
| Module routes (attendance, leave, overtime, dll) akan ditambah di
| Sprint 30 sesuai sprint-branch-strategy.md. File ini sengaja minimal
| sebagai bootstrap.
*/

// Endpoint paling dasar: /api/v1/user — return current user identity.
// Dipakai PWA untuk verifikasi session/token validity setelah login.
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    $user = $request->user();

    return response()->json([
        'id' => $user->id,
        'name' => $user->name,
        'email' => $user->email,
        'roles' => $user->getRoleNames(),
        'permissions' => $user->getAllPermissions()->pluck('name'),
    ]);
});
