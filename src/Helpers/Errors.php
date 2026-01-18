<?php

namespace GenericDatabase\Helpers;

/**
 * The `GenericDatabase\Helpers\Errors` class provides static methods for manipulating error display settings
 * and throwing exceptions with error messages and locations.
 *
 * Example Usage:
 * <code>
 * // Turn off error display
 * Errors::turnOff();
 *
 * // Turn on error display
 * Errors::turnOn();
 *
 * // Throw an exception and display the error message and location
 * Errors::throw(new Exception('An error occurred'));
 *
 * // Throw a custom exception with a custom error message and display the error message and location
 * Errors::newThrow(new Exception('An error occurred'), 'Custom error message');
 * </code>
 *
 * Main functionalities:
 * - Manipulating error display settings
 * - Throwing exceptions with error messages and locations
 *
 * Methods:
 * - `setError(mixed $value): mixed`: Sets the error display setting to the specified value and returns the previous value.
 * - `turnOff(): mixed`: Turns off error display by setting the error display setting to 0 and returns the previous value.
 * - `turnOn(): mixed`: Turns on error display by setting the error display setting to 1 and returns the previous value.
 * - `throw(object $exception): never`: Throws the specified exception and displays the error message and location.
 * - `newThrow(object $exception, object $message): never`: Throws a custom exception with the specified error message and displays the error message and location.
 *
 * Fields:
 * - `error`: Stores the previous error display setting.
 *
 * @package GenericDatabase\Helpers
 * @subpackage Errors
 */
class Errors
{
    /**
     * Store the previous error display setting.
     *
     * @var mixed
     */
    private static mixed $error;

    /**
     * Set the error display setting to the specified value and return the previous value.
     *
     * @param mixed $value The value to set the error display setting to.
     * @return mixed The previous error display setting.
     */
    private static function setError(mixed $value): mixed
    {
        self::$error = ini_set('display_errors', $value);
        return self::$error;
    }

    /**
     * Turn off error display by setting the error display setting to 0.
     *
     * @return mixed The previous error display setting.
     */
    public static function turnOff(): mixed
    {
        return self::setError(0);
    }

    /**
     * Turn on error display by setting the error display setting to 1.
     *
     * @return mixed The previous error display setting.
     */
    public static function turnOn(): mixed
    {
        return self::setError(1);
    }

    /**
     * Throw a custom exception with the specified error message and display the error message and location.
     *
     * @param object $exception The exception to throw.
     * @param string|null $message = null The custom error message.
     * @return string|false This method always throws an exception and does not return.
     */
    public static function throw(object $exception, ?string $message = null): string|false
    {
        return json_encode(
            ['message' => (is_null($message))
                ? $exception->getMessage()
                : $message, 'location' => "{$exception->getFile()}:{$exception->getLine()}"]
        );
    }
}

