<?php

namespace GenericDatabase\Engine\MySQLi;

use GenericDatabase\Helpers\Reflections;
use ReflectionException;

class MySQL
{
    final public const ATTR_OPT_CONNECT_TIMEOUT = 2;
    final public const ATTR_OPT_READ_TIMEOUT = 3;
    final public const ATTR_INIT_COMMAND = 1002;
    final public const ATTR_SET_CHARSET_NAME = 4;
    final public const ATTR_READ_DEFAULT_GROUP = 1007;
    final public const ATTR_OPT_INT_AND_FLOAT_NATIVE = 1011;
    final public const ATTR_OPT_SSL_VERIFY_SERVER_CERT = 1012;
    final public const ATTR_PERSISTENT = 13;
    final public const ATTR_AUTOCOMMIT = 14;

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
