<?php

namespace GenericDatabase\Engine\OCI;

use GenericDatabase\Helpers\Reflections;
use ReflectionException;

class OCI
{
    public const ATTR_CONNECT_TIMEOUT = 1001;
    public const ATTR_PERSISTENT = 13;

    protected static array $data = [];

    /**
     * @throws ReflectionException
     */
    public static function getAttribute(mixed $name): mixed
    {
        if (isset(self::$data[$name])) {
            if (is_int($name)) {
                $result = self::$data[Reflections::getClassConstantName(__CLASS__, $name)];
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
            self::$data[Reflections::getClassConstantName(__CLASS__, $name)] = $value;
        } else {
            self::$data[$name] = $value;
        }
    }
}
