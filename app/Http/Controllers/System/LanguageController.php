<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;

class LanguageController extends Controller
{
    /**
     * Update the user's language preference.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'language' => 'required|in:id,en',
        ]);

        // Always update session for immediate effect (guests & users)
        session(['locale' => $validated['language']]);
        App::setLocale($validated['language']);

        // If user is logged in, save preference to database
        if ($user = $request->user()) {
            $user->language = $validated['language'];
            $user->save();
        }

        return back();
    }
}
