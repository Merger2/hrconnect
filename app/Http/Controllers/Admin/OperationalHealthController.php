<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class OperationalHealthController extends Controller
{
    public function __invoke(): View
    {
        return view('admin.operational-health');
    }
}
