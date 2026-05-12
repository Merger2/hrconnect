<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class FaceNotRecognizedException extends Exception
{
    public function __construct(string $message = 'Wajah tidak dikenali. Pastikan wajah Anda terlihat jelas dan coba lagi.', $code = 422)
    {
        parent::__construct($message, $code);
    }

    public function render($request): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
        ], $this->getCode() ?: 400);
    }
}
