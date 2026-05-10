<?php

namespace App\Exceptions;
use Exception;

class GeofenceViolationException extends Exception
{
    public function __construct($message = 'Anda berada di luar area yang diizinkan. Pastikan Anda berada di lokasi yang benar dan coba lagi.', $code = 403)
    {
        parent::__construct($message, $code);
    }
}