<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Support\UserHomeCommandCenterService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, UserHomeCommandCenterService $commandCenter)
    {
        return view('pages.home', [
            'homeCommandCenter' => $commandCenter->forUser($request->user()),
        ]);
    }
}
