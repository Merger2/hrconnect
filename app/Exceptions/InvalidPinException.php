<?php

namespace App\Exceptions;

use Exception;

class InvalidPinException extends Exception
{
    public function __construct(string $message = "Otorisasi Gagal: PIN yang Anda masukkan salah atau tidak terdaftar.", $code = 401)
    {
        parent::__construct($message, $code);
    }
}
