<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class E2eLoginController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        if (! app()->environment(['local', 'testing'])) {
            abort(404);
        }

        $expectedToken = (string) config('services.e2e.login_token', 'local-apk-e2e');
        $providedToken = (string) $request->query('token', '');

        if (! hash_equals($expectedToken, $providedToken)) {
            abort(403);
        }

        $user = User::query()->where('email', (string) $request->query('email', ''))->firstOrFail();

        if (! $user->canAuthenticate()) {
            abort(403);
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->to((string) $request->query('to', '/home'));
    }
}
