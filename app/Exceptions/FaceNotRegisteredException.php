<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class FaceNotRegisteredException extends Exception
{
    public function __construct(string $message = 'Data biometrik wajah Anda belum terdaftar. Silakan hubungi HRD.')
    {
        parent::__construct($message, 400);
    }

    public function render(): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
        ], $this->getCode() ?: 400);
    }
}
