<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;

class TestErrorController extends Controller
{
    public function __invoke(string $code): never
    {
        if (! app()->environment(['local', 'testing']) && ! config('app.debug')) {
            abort(404);
        }

        $statusCode = (int) $code;
        if (! in_array($statusCode, [401, 402, 403, 404, 405, 408, 413, 419, 429, 500, 502, 503, 504], true)) {
            abort(404);
        }

        abort($statusCode);
    }
}
