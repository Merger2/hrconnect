<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class AntiFakeGPSException extends Exception
{
    public function __construct(string $message = 'Peringatan: Aplikasi Fake GPS terdeteksi aktif di perangkat Anda!', $code = 403)
    {
        parent::__construct($message, $code);
    }

    public function render($request): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
        ], $this->getCode() ?: 403);
    }
}
