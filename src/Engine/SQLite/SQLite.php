<?php

namespace GenericDatabase\Engine\SQLite;

use GenericDatabase\Traits\Reflections;

class SQLite
{
    public const ATTR_OPEN_READONLY = 1;
    public const ATTR_OPEN_READWRITE = 2;
    public const ATTR_OPEN_CREATE = 4;
    public const ATTR_CONNECT_TIMEOUT = 12;
    public const ATTR_PERSISTENT = 13;
    public const ATTR_AUTOCOMMIT = 14;

    protected static $data = [];

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
