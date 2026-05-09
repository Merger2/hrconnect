<?php

namespace App\Exceptions;

use Exception;

class NotClockedInException extends Exception
{
    public function __construct(string $message = 'Anda belum melakukan clock in hari ini. Harap lakukan clock in sebelum melakukan clock out.', $code = 400)
    {
        parent::__construct($message, $code);
    }
}
