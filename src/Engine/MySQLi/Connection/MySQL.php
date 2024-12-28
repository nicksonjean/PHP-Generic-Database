<?php

namespace GenericDatabase\Engine\MySQLi\Connection;

use GenericDatabase\Helpers\Reflections;
use ReflectionException;

class MySQL
{
    /**
     * Connection attribute to set the connection timeout.
     */
    final public const ATTR_OPT_CONNECT_TIMEOUT = 2;

    /**
     * Connection attribute to set the read timeout.
     */
    final public const ATTR_OPT_READ_TIMEOUT = 3;

    /**
     * Connection attribute to set persistence of the connection.
     */
    final public const ATTR_PERSISTENT = 13;

    /**
     * Connection attribute to set the auto-commit mode.
     */
    final public const ATTR_AUTOCOMMIT = 14;

    /**
     * Connection attribute to set the initial command.
     */
    final public const ATTR_INIT_COMMAND = 1002;

    /**
     * Connection attribute to set the character set.
     */
    final public const ATTR_SET_CHARSET_NAME = 4;

    /**
     * Connection attribute to set the read default group.
     */
    final public const ATTR_READ_DEFAULT_GROUP = 1007;

    /**
     * Connection attribute to set integer and float values as native types.
     */
    final public const ATTR_OPT_INT_AND_FLOAT_NATIVE = 1011;

    /**
     * Connection attribute to verify the server certificate in SSL.
     */
    final public const ATTR_OPT_SSL_VERIFY_SERVER_CERT = 1012;

    /**
     * Connection attribute to set the default fetch mode.
     */
    final public const ATTR_DEFAULT_FETCH_MODE = 1100;

    /**
     * Fetch mode that starts fetching rows only when they are requested.
     */
    final public const FETCH_LAZY = 1;

    /**
     * Constant for the fetch mode representing fetching as an associative array
     */
    final public const FETCH_ASSOC = 2;

    /**
     * Constant for the fetch mode representing fetching as a numeric array
     */
    final public const FETCH_NUM = 3;

    /**
     * Constant for the fetch mode representing fetching as both a numeric and associative array
     */
    final public const FETCH_BOTH = 4;

    /**
     * Constant for the fetch mode representing fetching as an object
     */
    final public const FETCH_OBJ = 5;

    /**
     * Fetch mode that requires explicit binding of PHP variables to fetch values.
     */
    final public const FETCH_BOUND = 6;

    /**
     * Constant for the fetch mode representing fetching a single column
     */
    final public const FETCH_COLUMN = 7;

    /**
     * Constant for the fetch mode representing fetching into a new instance of a specified class
     */
    final public const FETCH_CLASS = 8;

    /**
     * Constant for the fetch mode representing fetching into an existing object
     */
    final public const FETCH_INTO = 9;

    /**
     * Array of data attributes.
     *
     * @var array
     */
    protected static array $dataAttribute = [];

    /**
     * Retrieves the value of a specific attribute by name or constant.
     *
     * @param mixed $name The name of the attribute or the constant associated with it.
     * @return mixed The value of the attribute if found; null otherwise.
     * @throws ReflectionException If the reflection class cannot find the constant name.
     */
    public static function getAttribute(mixed $name): mixed
    {
        if (isset(self::$dataAttribute[$name])) {
            if (is_int($name)) {
                $result = self::$dataAttribute[Reflections::getClassConstantName(self::class, $name)];
            } else {
                $result = self::$dataAttribute[$name];
            }
        } else {
            $result = null;
        }
        return $result;
    }

    /**
     * Sets the value of a specific attribute by name or constant.
     *
     * @param mixed $name The name of the attribute or the constant associated with it. If null, the value is appended.
     * @param mixed $value The value to set for the specified attribute.
     * @return void
     * @throws ReflectionException If the reflection class cannot find the constant name.
     */
    public static function setAttribute(mixed $name, mixed $value): void
    {
        if (is_null($name)) {
            self::$dataAttribute[] = $value;
        } elseif (is_int($name)) {
            self::$dataAttribute[Reflections::getClassConstantName(self::class, $name)] = $value;
        } else {
            self::$dataAttribute[$name] = $value;
        }
    }
}
