<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;

class TermsOfServiceController extends Controller
{
    public function show(Request $request)
    {
        $termsFile = Jetstream::localizedMarkdownPath('terms.md');

        return view('terms', [
            'terms' => file_get_contents($termsFile),
        ]);
    }
}
