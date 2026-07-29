<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Jetstream\Jetstream;

class PrivacyPolicyController extends Controller
{
    public function show(Request $request)
    {
        $policyFile = Jetstream::localizedMarkdownPath('policy.md');

        return view('policy', [
            'policy' => file_get_contents($policyFile),
        ]);
    }
}
