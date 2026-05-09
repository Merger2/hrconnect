<?php

namespace App\Exceptions;

use Exception;

class AntiFakeGPSException extends Exception
{
    public function __construct(string $message =  'Peringatan: Aplikasi Fake GPS terdeteksi aktif di perangkat Anda!', $code = 403)
    {
        parent::__construct($message, $code);
    }
}