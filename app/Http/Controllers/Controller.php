<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

abstract class Controller
{
    use AuthorizesRequests;

    protected function employeeNotFound(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => 'Akun Anda belum terhubung dengan data karyawan.',
        ], 404);
    }
}
