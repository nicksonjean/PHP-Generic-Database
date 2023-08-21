<?php

namespace GenericDatabase\Engine\SQLite;

use GenericDatabase\Helpers\Reflections;

if (!defined('SQLITE_FETCH_NUM')) {
    define('SQLITE_FETCH_NUM', 8);
}
if (!defined('SQLITE_FETCH_OBJ')) {
    define('SQLITE_FETCH_OBJ', 9);
}
if (!defined('SQLITE_FETCH_BOTH')) {
    define('SQLITE_FETCH_BOTH', 10);
}
if (!defined('SQLITE_FETCH_INTO')) {
    define('SQLITE_FETCH_INTO', 11);
}
if (!defined('SQLITE_FETCH_CLASS')) {
    define('SQLITE_FETCH_CLASS', 12);
}
if (!defined('SQLITE_FETCH_ASSOC')) {
    define('SQLITE_FETCH_ASSOC', 13);
}
if (!defined('SQLITE_FETCH_COLUMN')) {
    define('SQLITE_FETCH_COLUMN', 14);
}

if (!defined('FETCH_NUM')) {
    define('FETCH_NUM', 8);
}
if (!defined('FETCH_OBJ')) {
    define('FETCH_OBJ', 9);
}
if (!defined('FETCH_BOTH')) {
    define('FETCH_BOTH', 10);
}
if (!defined('FETCH_INTO')) {
    define('FETCH_INTO', 11);
}
if (!defined('FETCH_CLASS')) {
    define('FETCH_CLASS', 12);
}
if (!defined('FETCH_ASSOC')) {
    define('FETCH_ASSOC', 13);
}
if (!defined('FETCH_COLUMN')) {
    define('FETCH_COLUMN', 14);
}

class SQLite
{
    public const ATTR_OPEN_READONLY = 1;
    public const ATTR_OPEN_READWRITE = 2;
    public const ATTR_OPEN_CREATE = 4;
    public const ATTR_CONNECT_TIMEOUT = 12;
    public const ATTR_PERSISTENT = 13;
    public const ATTR_AUTOCOMMIT = 14;

    /**
     * Data to Get and Setter for Attribute
     * @var array $data
     */
    protected static array $data = [];

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
