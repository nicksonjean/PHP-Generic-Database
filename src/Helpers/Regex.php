<?php

namespace GenericDatabase\Helpers;

class Regex
{
    /**
     * Regex pattern for only numbers
     */
    private static $patterns = [
        'onlyNumbers' =>
        "/^(?:-(?:[1-9](?:\\d{0,2}(?:,\\d{3})+|\\d*))|(?:0|(?:[1-9](?:\\d{0,2}(?:,\\d{3})+|\\d*))))(?:.\\d+|)$/"
    ];

    /**
     * Check if a value is numeric
     *
     * @param string $subject Value to be checked
     * @return int|false True if the value is numeric, false otherwise
     */
    public static function isNumber(string $subject): int|false
    {
        return preg_match(self::$patterns['onlyNumbers'], (string) $subject);
    }
    /**
     * Check "Booleanic" Conditions :)
     *
     * @param mixed $value Can be anything (string, bol, integer, etc.)
     * @return mixed
     * Returns TRUE  for "1", "true", "on" and "yes",
     * Returns FALSE for "0", "false", "off" and "no",
     * Returns NULL otherwise.
     */
    public static function isBoolean(mixed $value): mixed
    {
        if (!isset($value)) {
            return null;
        }
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
    }
}