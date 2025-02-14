<?php

namespace GenericDatabase\Helpers\Types\Scalars;

class Strings
{
    public static function toCamelize($input, $separator = '_'): string
    {
        return lcfirst(str_replace($separator, '', ucwords((string) $input, $separator)));
    }
}
