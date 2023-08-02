<?php

namespace GenericDatabase\Engine\FBird;

use GenericDatabase\Helpers\Reflections;

if (!defined('FBIRD_FETCH_NUM')) {
    define('FBIRD_FETCH_NUM', 8);
}
if (!defined('FBIRD_FETCH_OBJ')) {
    define('FBIRD_FETCH_OBJ', 9);
}
if (!defined('FBIRD_FETCH_BOTH')) {
    define('FBIRD_FETCH_BOTH', 10);
}
if (!defined('FBIRD_FETCH_INTO')) {
    define('FBIRD_FETCH_INTO', 11);
}
if (!defined('FBIRD_FETCH_CLASS')) {
    define('FBIRD_FETCH_CLASS', 12);
}
if (!defined('FBIRD_FETCH_ASSOC')) {
    define('FBIRD_FETCH_ASSOC', 13);
}
if (!defined('FBIRD_FETCH_COLUMN')) {
    define('FBIRD_FETCH_COLUMN', 14);
}

if (!defined('IBASE_FETCH_NUM')) {
    define('IBASE_FETCH_NUM', 8);
}
if (!defined('IBASE_FETCH_OBJ')) {
    define('IBASE_FETCH_OBJ', 9);
}
if (!defined('IBASE_FETCH_BOTH')) {
    define('IBASE_FETCH_BOTH', 10);
}
if (!defined('IBASE_FETCH_INTO')) {
    define('IBASE_FETCH_INTO', 11);
}
if (!defined('IBASE_FETCH_CLASS')) {
    define('IBASE_FETCH_CLASS', 12);
}
if (!defined('IBASE_FETCH_ASSOC')) {
    define('IBASE_FETCH_ASSOC', 13);
}
if (!defined('IBASE_FETCH_COLUMN')) {
    define('IBASE_FETCH_COLUMN', 14);
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

class FBird
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
