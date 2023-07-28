<?php

namespace GenericDatabase\Engine\PgSQL;

use GenericDatabase\Helpers\Reflections;

define('PGSQL_FETCH_NUM', 8);
define('PGSQL_FETCH_OBJ', 9);
define('PGSQL_FETCH_BOTH', 10);
define('PGSQL_FETCH_INTO', 11);
define('PGSQL_FETCH_CLASS', 12);
define('PGSQL_FETCH_ASSOC', 13);
define('PGSQL_FETCH_COLUMN', 14);

define('FETCH_NUM', 8);
define('FETCH_OBJ', 9);
define('FETCH_BOTH', 10);
define('FETCH_INTO', 11);
define('FETCH_CLASS', 12);
define('FETCH_ASSOC', 13);
define('FETCH_COLUMN', 14);

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
