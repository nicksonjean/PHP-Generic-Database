<?php

namespace GenericDatabase\Traits;

trait Errors
{
    /**
     * Store a Error
     */
    private static $error;

    /**
     * Get error from display_errors directive
     *
     * @return string|false
     */
    private static function getError(): string|false
    {
        self::$error = ini_get('display_errors');
        return self::$error;
    }

    /**
     * Set error to display_errors directive
     *
     * @param string|int|float|bool|null $value Argument to be tested
     * @return string|false
     */
    private static function setError($value): string|int|float|bool|null
    {
        self::$error = ini_set('display_errors', $value);
        return self::$error;
    }

    /**
     * Turn off errors
     *
     * @return string|false
     */
    public static function turnOff()
    {
        return self::setError(0);
    }

    /**
     * Turn on errors
     *
     * @return string|false
     */
    public static function turnOn()
    {
        return self::setError(1);
    }

    /**
     * Throw a exception
     *
     * @param object $exception Argument object of the \Exception class
     * @return never
     */
    public static function throw($exception): never
    {
        die(json_encode(array(
            'message' => $exception->getMessage(),
            'location' => "{$exception->getFile()}:{$exception->getLine()}"
        )));
    }

    /**
     * Launch a new Throw by a exception
     *
     * @param object $exception Argument object of the \Exception class
     * @param object $message Custom string message from exception
     * @return never
     */
    public static function newThrow($exception, $message): never
    {
        die(json_encode(array(
            'message' => $message,
            'location' => "{$exception->getFile()}:{$exception->getLine()}"
        )));
    }
}
