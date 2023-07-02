<?php

namespace GenericDatabase\Helpers;

class EnumFactory
{
    private static function setType($value)
    {
        return gettype($value) === 'string' ? "'" . $value . "'" : $value;
    }

    public static function create($class, $constants)
    {
        $declaration = '';
        foreach ($constants as $name => $value) {
            $declaration .= 'const ' . $name . ' = ' . self::setType($value) . ';';
        }

        eval("class $class extends Enum { $declaration protected static function who() { return __CLASS__; } }");
        $class::registerConstants($constants);
    }
}
