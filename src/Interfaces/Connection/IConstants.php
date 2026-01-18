<?php

namespace GenericDatabase\Interfaces\Connection;

/**
 * This interface defines a set of constants to be used for database connections.
 * Implementing classes should provide specific values for these constants.
 *
 * @package PHP-Generic-Database\Interfaces\Connection
 * @subpackage IConstants
 */
interface IConstants
{
    /**
     * Retrieves the value of a specific attribute by name or constant.
     *
     * @param mixed $name The name of the attribute or the constant associated with it.
     * @return mixed The value of the attribute if found; null otherwise.
     */
    public static function getAttribute(mixed $name): mixed;

    /**
     * Sets the value of a specific attribute by name or constant.
     *
     * @param mixed $name The name of the attribute or the constant associated with it. If null, the value is appended.
     * @param mixed $value The value to set for the specified attribute.
     * @return void
     */
    public static function setAttribute(mixed $name, mixed $value): void;
}
