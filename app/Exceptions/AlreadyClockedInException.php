<?php

namespace App\Exceptions;

use Exception;

class AlreadyClockedInException extends Exception
{
    public function __construct(string $message = 'Anda sudah melakukan clock in hari ini. Harap lakukan clock out sebelum melakukan clock in lagi.', $code = 409)
    {
        parent::__construct($message, $code);
    }
}
