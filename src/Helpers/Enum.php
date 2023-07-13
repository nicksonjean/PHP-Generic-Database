<?php

namespace GenericDatabase\Helpers;

class Enum
{
    protected static $constantToClassMap = array();

    protected static function who()
    {
        return __CLASS__;
    }

    public static function registerConstants($constants)
    {
        $class = static::who();
        foreach ($constants as $name) {
            self::$constantToClassMap[$class . '_' . $name] = new $class();
        }
    }

    public static function __callStatic($name, $arguments)
    {
        return self::$constantToClassMap[static::who() . '_' . $name];
    }
}
