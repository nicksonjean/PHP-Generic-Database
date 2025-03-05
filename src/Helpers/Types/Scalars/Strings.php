<?php

namespace GenericDatabase\Helpers\Types\Scalars;

/**
 * Class Strings
 *
 * This class provides helper methods for string manipulation.
 *
 * @package GenericDatabase\Helpers\Types\Scalars
 */
class Strings
{
    /**
     * Converts a string to camelCase.
     *
     * This method takes an input string and converts it to camelCase format.
     * By default, it uses an underscore ('_') as the word separator.
     *
     * @param string $input The input string to be converted.
     * @param string $separator The separator used to split words in the input string. Default is '_'.
     * @return string The camelCase formatted string.
     */
    public static function toCamelize($input, $separator = '_'): string
    {
        return lcfirst(str_replace($separator, '', ucwords((string) $input, $separator)));
    }
}
