<?php

namespace GenericDatabase\Engine\MySQLi;

use GenericDatabase\Helpers\Reflections;

if (!defined('MYSQLI_FETCH_NUM')) {
    define('MYSQLI_FETCH_NUM', 8);
}
if (!defined('MYSQLI_FETCH_OBJ')) {
    define('MYSQLI_FETCH_OBJ', 9);
}
if (!defined('MYSQLI_FETCH_BOTH')) {
    define('MYSQLI_FETCH_BOTH', 10);
}
if (!defined('MYSQLI_FETCH_INTO')) {
    define('MYSQLI_FETCH_INTO', 11);
}
if (!defined('MYSQLI_FETCH_CLASS')) {
    define('MYSQLI_FETCH_CLASS', 12);
}
if (!defined('MYSQLI_FETCH_ASSOC')) {
    define('MYSQLI_FETCH_ASSOC', 13);
}
if (!defined('MYSQLI_FETCH_COLUMN')) {
    define('MYSQLI_FETCH_COLUMN', 14);
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

class MySQL
{
    public const ATTR_OPT_CONNECT_TIMEOUT = 2;
    public const ATTR_OPT_READ_TIMEOUT = 3;
    public const ATTR_INIT_COMMAND = 1002;
    public const ATTR_SET_CHARSET_NAME = 4;
    public const ATTR_READ_DEFAULT_GROUP = 1007;
    public const ATTR_OPT_INT_AND_FLOAT_NATIVE = 1011;
    public const ATTR_OPT_SSL_VERIFY_SERVER_CERT = 1012;
    public const ATTR_PERSISTENT = 13;
    public const ATTR_AUTOCOMMIT = 14;

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
