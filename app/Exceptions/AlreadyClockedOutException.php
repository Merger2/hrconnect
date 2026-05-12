<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class AlreadyClockedOutException extends Exception
{
    public function __construct(string $message = 'Anda sudah melakukan clock out hari ini.', $code = 409)
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
