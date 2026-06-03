<?php

namespace App\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class NotClockedInException extends Exception
{
    public function __construct(string $message = 'Tidak ada absensi masuk hari ini.')
    {
        parent::__construct($message, Response::HTTP_CONFLICT);
    }

    public function render(): Response
    {
        return response()->json([
            'status' => 'error',
            'message' => $this->getMessage(),
        ], Response::HTTP_CONFLICT);
    }
}
