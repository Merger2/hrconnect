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

        // Update session untuk efek langsung (guest & user)
        session(['locale' => $validated['language']]);
        App::setLocale($validated['language']);

        // Q1: `$user->language = ...` + save() dihapus — kolom `language`
        // TIDAK ADA di tabel users (0 migration) → UPDATE users SET language
        // = QueryException 500 setiap ganti bahasa. Preferensi bahasa hidup di
        // session (locale) + tidak ada konsumen lain yang membaca kolom tsb
        // (middleware SetLocale/SetUserLocale sudah dihapus — dead code).

        return back();
    }
}
