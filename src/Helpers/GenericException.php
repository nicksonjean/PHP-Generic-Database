<?php

namespace GenericDatabase\Helpers;

use Exception;
use Throwable;

class GenericException extends Exception implements Throwable
{
    /**
     * @var int
     */
    protected $code;

    /**
     * @var string
     */
    protected string $file;

    /**
     * @var int
     */
    protected int $line;

    /**
     * @var string
     */
    protected $message;

    /**
     * @var int
     */
    protected int $severity = E_ERROR;

    /**
     * Gets the exception severity
     * Returns the severity of the exception.
     * @return int Returns the severity level of the exception.
     */
    final public function getSeverity(): int
    {
        return $this->severity;
    }

    final protected function noop(mixed $value): mixed
    {
        return $value;
    }

    /**
     * Constructs the Exception.
     *
     * @param string|null $message The Exception message to throw.
     * @param int|null $code The Exception code.
     * @param int|null $severity The severity level of the exception.
     * @param string|null $filename The filename where the exception is thrown.
     * @param int|null $line The line number where the exception is thrown.
     * @param Throwable|null $previous The previous exception used for the exception chaining.
     * @return void
     */
    public function __construct(
        $message = "",
        $code = 0,
        $severity = E_ERROR,
        string $filename = null,
        int $line = null,
        Throwable $previous = null
    ) {
        $this->noop($message);
        $this->noop($code);
        $this->noop($severity);
        $this->noop($filename);
        $this->noop($line);
        $this->noop($previous);
    }
}
