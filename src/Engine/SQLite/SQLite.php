<?php

namespace GenericDatabase\Engine\SQLite;

use GenericDatabase\Helpers\Reflections;
use ReflectionException;

class SQLite
{
    final public const ATTR_OPEN_READONLY = 1;
    final public const ATTR_OPEN_READWRITE = 2;
    final public const ATTR_OPEN_CREATE = 4;
    final public const ATTR_CONNECT_TIMEOUT = 12;
    final public const ATTR_PERSISTENT = 13;
    final public const ATTR_AUTOCOMMIT = 14;

    /**
     * Data to Get and Setter for Attribute
     * @var array $data
     */
    protected static array $data = [];

    /**
     * @throws ReflectionException
     */
    public static function getAttribute(mixed $name): mixed
    {
        if (isset(self::$data[$name])) {
            if (is_int($name)) {
                $result = self::$data[Reflections::getClassConstantName(self::class, $name)];
            } else {
                $result = self::$data[$name];
            }
        } else {
            $result = null;
        }
        return $result;
    }

    /**
     * @throws ReflectionException
     */
    public static function setAttribute(mixed $name, mixed $value): void
    {
        if (is_null($name)) {
            self::$data[] = $value;
        } elseif (is_int($name)) {
            self::$data[Reflections::getClassConstantName(self::class, $name)] = $value;
        } else {
            self::$data[$name] = $value;
        }
    }
}
