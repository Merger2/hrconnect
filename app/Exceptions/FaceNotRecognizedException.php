<?php

namespace App\Exceptions;
use Exception;

class FaceNotRecognizedException extends Exception
{
    public function __construct(string $message = 'Wajah tidak dikenali. Pastikan wajah Anda terlihat jelas dan coba lagi.', $code = 422)
    {
        parent::__construct($message, $code);
    }
}