<?php

namespace GenericDatabase\Engine\Firebird;

use GenericDatabase\Helpers\Reflections;
use ReflectionException;

class Firebird
{
    final public const ATTR_CONNECT_TIMEOUT = 1001;
    final public const ATTR_CONNECT_ASYNC = 1002;
    final public const ATTR_CONNECT_FORCE_NEW = 1003;
    final public const ATTR_PERSISTENT = 13;

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