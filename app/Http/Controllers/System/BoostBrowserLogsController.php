<?php

declare(strict_types=1);

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Boost MCP browser logger endpoint — required to prevent infinite error loop.
 *
 * Dev-only: abort 404 di luar APP_ENV local/testing (sama dengan guard
 * E2eLoginController/E2eDocumentUploadController) supaya tidak ada permukaan
 * endpoint dev yang terbuka di produksi.
 */
class BoostBrowserLogsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        if (! app()->environment(['local', 'testing'])) {
            abort(404);
        }

        return response()->json(['status' => 'ok']);
    }
}
