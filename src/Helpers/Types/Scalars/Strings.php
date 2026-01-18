<?php

namespace GenericDatabase\Helpers\Types\Scalars;

/**
 * The `GenericDatabase\Helpers\Types\Scalars\Strings` class provides methods for manipulating strings.
 *
 * @package GenericDatabase\Helpers\Types\Scalars
 * @subpackage Strings
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
    public static function toCamelize(string $input, string $separator = '_'): string
    {
        return lcfirst(str_replace($separator, '', ucwords($input, $separator)));
    }

    /**
     * Repeat a given string a specified number of times.
     *
     * This method takes a number and a string and returns a new string with the given string repeated
     * the specified number of times.
     *
     * @param int $num The number of times to repeat the string.
     * @param string $string The string to be repeated.
     * @return string The repeated string.
     */
    public static function strRpeat(int $num, string $string): string
    {
        $result = "";
        for ($x = 0; $x < $num; $x++) {
            $result .= $string;
        }
        return $result;
    }
}

