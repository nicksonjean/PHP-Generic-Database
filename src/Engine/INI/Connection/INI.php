<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\INI\Connection;

use GenericDatabase\Helpers\Reflections;
use ReflectionException;

/**
 * INI-specific constants and attribute management.
 *
 * @package GenericDatabase\Engine\INI\Connection
 */
class INI
{
    /**
     * INI scanner mode for typed values (boolean, null, integer).
     */
    public const ATTR_INI_SCANNER_TYPED = 3001;

    /**
     * Connection attribute to set persistence of the connection.
     */
    public const ATTR_PERSISTENT = 13;

    /**
     * Connection attribute to set the auto-commit mode.
     */
    public const ATTR_AUTOCOMMIT = 14;

    /**
     * Connection attribute to set the connection timeout.
     */
    public const ATTR_CONNECT_TIMEOUT = 1001;

    /**
     * Connection attribute to set the default fetch mode.
     */
    public const ATTR_DEFAULT_FETCH_MODE = 1100;

    /**
     * Connection attribute to enable pretty print output.
     */
    public const ATTR_PRETTY_PRINT = 2001;

    /**
     * Connection attribute to set encoding.
     */
    public const ATTR_ENCODING = 2002;

    /**
     * Connection attribute to enable auto-save after each operation.
     */
    public const ATTR_AUTO_SAVE = 2003;

    /**
     * Connection attribute to enable schema validation.
     */
    public const ATTR_SCHEMA_VALIDATION = 2004;

    /**
     * Connection attribute to set the default report mode.
     */
    public const ATTR_REPORT = 1110;

    /**
     * Turns reporting off alias for MYSQLI_REPORT_OFF
     */
    public const REPORT_OFF = 0;

    /**
     * Report errors from mysqli function calls alias for MYSQLI_REPORT_ERROR
     */
    public const REPORT_ERROR = 1;

    /**
     * Throw exception for errors instead of warnings alias for MYSQLI_REPORT_STRICT
     */
    public const REPORT_STRICT = 2;

    /**
     * Report if no index or bad index was used in a query alias for MYSQLI_REPORT_INDEX
     */
    public const REPORT_INDEX = 4;

    /**
     * Report all errors alias for MYSQLI_REPORT_ALL
     */
    public const REPORT_ALL = 255;

    /**
     * Fetch mode that starts fetching rows only when they are requested.
     */
    public const FETCH_LAZY = 1;

    /**
     * Constant for the fetch mode representing fetching as an associative array
     */
    public const FETCH_ASSOC = 2;

    /**
     * Constant for the fetch mode representing fetching as a numeric array
     */
    public const FETCH_NUM = 3;

    /**
     * Constant for the fetch mode representing fetching as both a numeric and associative array
     */
    public const FETCH_BOTH = 4;

    /**
     * Constant for the fetch mode representing fetching as an object
     */
    public const FETCH_OBJ = 5;

    /**
     * Fetch mode that requires explicit binding of PHP variables to fetch values.
     */
    public const FETCH_BOUND = 6;

    /**
     * Constant for the fetch mode representing fetching a single column
     */
    public const FETCH_COLUMN = 7;

    /**
     * Constant for the fetch mode representing fetching into a new instance of a specified class
     */
    public const FETCH_CLASS = 8;

    /**
     * Constant for the fetch mode representing fetching into an existing object
     */
    public const FETCH_INTO = 9;

    /**
     * AND operator constant
     */
    public const AND = 'AND';

    /**
     * OR operator constant
     */
    public const OR = 'OR';

    /**
     * Ascending sort order
     */
    public const ASC = 1;

    /**
     * Descending sort order
     */
    public const DESC = 0;

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
        $lookupKey = is_int($name)
            ? Reflections::getClassConstantName(static::class, $name)
            : $name;

        if ($lookupKey === false) {
            return null;
        }

        return self::$dataAttribute[$lookupKey] ?? null;
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
            self::$dataAttribute[Reflections::getClassConstantName(static::class, $name)] = $value;
        } else {
            self::$dataAttribute[$name] = $value;
        }
    }

    /**
     * Implements regex search pattern.
     *
     * @param string $pattern Regex pattern.
     * @param int $pregMatchFlags Flags for preg_match.
     * @return object The regex object.
     */
    public static function regex(string $pattern, int $pregMatchFlags = 0): object
    {
        $regexObj = new \stdClass();
        $regexObj->is_regex = true;
        $regexObj->value = $pattern;
        $regexObj->options = $pregMatchFlags;

        return $regexObj;
    }

    /**
     * Get default INI encoding flags (reserved for future use).
     * PHP has no native ini_encode; encoding is handled by Helpers\Parsers\INI.
     *
     * @return int The encoding flags (0 = default).
     */
    public static function getDefaultEncodingFlags(): int
    {
        return 0;
    }
}
