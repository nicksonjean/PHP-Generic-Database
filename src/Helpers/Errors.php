<?php

namespace GenericDatabase\Helpers;

class Errors
{
    /**
     * Store a Error
     */
    private static mixed $error;

    /**
     * Set error to display_errors directive
     *
     * @param mixed $value Argument to be tested
     * @return mixed
     */
    private static function setError(mixed $value): mixed
    {
        self::$error = ini_set('display_errors', $value);
        return self::$error;
    }

    /**
     * Turn off errors
     *
     * @return string|false
     */
    public static function turnOff(): mixed
    {
        return self::setError(0);
    }

    /**
     * Turn on errors
     *
     * @return string|false
     */
    public static function turnOn(): mixed
    {
        return self::setError(1);
    }

    /**
     * Throw exception
     *
     * @param object $exception Argument object of the \Exception class
     * @return never
     */
    public static function throw(object $exception): never
    {
        die(json_encode(array(
            'message' => $exception->getMessage(),
            'location' => "{$exception->getFile()}:{$exception->getLine()}"
        )));
    }

    /**
     * Launch a new Throw by exception
     *
     * @param object $exception Argument object of the \Exception class
     * @param object $message Custom string message from exception
     * @return never
     */
    public static function newThrow(object $exception, object $message): never
    {
        die(json_encode(array(
            'message' => $message,
            'location' => "{$exception->getFile()}:{$exception->getLine()}"
        )));
    }
}
