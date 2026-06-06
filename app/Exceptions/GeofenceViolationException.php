<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class GeofenceViolationException extends Exception
{
    public function __construct($message = 'Anda berada di luar area yang diizinkan.', $code = 403)
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
