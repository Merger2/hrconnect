<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class LegacyRedirectController extends Controller
{
    public function enterpriseSupport(): RedirectResponse
    {
        return redirect('https://wa.me/628123456789');
    }

    public function commercial(): RedirectResponse
    {
        return redirect()->route('admin.dashboard');
    }
}
