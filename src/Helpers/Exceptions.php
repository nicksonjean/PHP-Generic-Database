<?php

namespace GenericDatabase\Helpers;

use Exception;
use Throwable;

/**
 * This `GenericDatabase\Helpers\Exceptions` class is a custom exception that extends the built-in
 * Exception class in PHP. It allows for the creation of custom exceptions with a default message, code,
 * and previous exception.
 *
 * Example Usage:
 * <code>
 * $exception = new Exceptions("This is a custom exception", 500);
 * throw $exception;
 * </code>
 *
 * Methods:
 * - `__construct($message = "Custom Exception", $code = 0, Throwable $previous = null)`: Constructor method that sets the exception message, code, and previous exception using the parent constructor from the Exception class.
 *
 * @package GenericDatabase\Helpers
 * @subpackage Exceptions
 */
class Exceptions extends Exception
{
    /**
     * Constructor method for Exceptions
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param Throwable|null $previous The previous exception
     */
    public function __construct(string $message = "Exception", int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
