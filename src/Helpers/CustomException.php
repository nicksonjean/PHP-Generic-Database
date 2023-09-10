<?php

namespace GenericDatabase\Helpers;

use Exception;
use Throwable;

class CustomException extends Exception
{
    public function __construct($message = "Custom Exception", $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
