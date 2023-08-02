<?php

namespace GenericDatabase\Engine\PgSQL;

use GenericDatabase\Helpers\Reflections;

if (!defined('PGSQL_FETCH_NUM')) {
    define('PGSQL_FETCH_NUM', 8);
}
if (!defined('PGSQL_FETCH_OBJ')) {
    define('PGSQL_FETCH_OBJ', 9);
}
if (!defined('PGSQL_FETCH_BOTH')) {
    define('PGSQL_FETCH_BOTH', 10);
}
if (!defined('PGSQL_FETCH_INTO')) {
    define('PGSQL_FETCH_INTO', 11);
}
if (!defined('PGSQL_FETCH_CLASS')) {
    define('PGSQL_FETCH_CLASS', 12);
}
if (!defined('PGSQL_FETCH_ASSOC')) {
    define('PGSQL_FETCH_ASSOC', 13);
}
if (!defined('PGSQL_FETCH_COLUMN')) {
    define('PGSQL_FETCH_COLUMN', 14);
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

class PgSQL
{
    public const ATTR_CONNECT_TIMEOUT = 1001;
    public const ATTR_CONNECT_ASYNC = 1002;
    public const ATTR_CONNECT_FORCE_NEW = 1003;
    public const ATTR_PERSISTENT = 13;

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
