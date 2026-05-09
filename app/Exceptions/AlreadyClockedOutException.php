<?php

namespace App\Exceptions;

use Exception;

class AlreadyClockedOutException extends Exception
{
    public function __construct(string $message = 'Anda sudah melakukan clock out hari ini. Harap lakukan clock in sebelum melakukan clock out lagi.', $code = 409)
    {
        parent::__construct($message, $code);
    }
}
