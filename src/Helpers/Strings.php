<?php

namespace GenericDatabase\Helpers;

class Strings
{

    public static function toCamelize($input, $separator = '_')
    {
        return lcfirst(str_replace($separator, '', ucwords((string) $input, $separator)));
    }
}
