<?php

namespace GenericDatabase\Helpers;

class Regex
{
    /**
     * Regex pattern for only numbers
     */
    private static $patterns = [
        'onlyNumbers' =>
        "/^(?:-(?:[1-9](?:\\d{0,2}(?:,\\d{3})+|\\d*))|(?:0|(?:[1-9](?:\\d{0,2}(?:,\\d{3})+|\\d*))))(?:.\\d+|)$/",
        'noBinding' => '/(:[a-zA-Z]{1,})/i'
    ];

    /**
     * Check if a value is numeric
     *
     * @param string $value Value to be checked
     * @return int|false True if the value is numeric, false otherwise
     */
    public static function isNumber(string $value): int|false
    {
        return preg_match(self::$patterns['onlyNumbers'], (string) $value);
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

    /**
     * Replace binding param name to another bind type
     *
     * @param string $value The SQL Query with binding names
     * @param bool $bindType The binding type Value
     * @return string The SQL Query post processed with binding types
     */
    public static function noBinding(string $value, bool $bindType = true)
    {
        return $bindType
            ? preg_replace(self::$patterns['noBinding'], '?', $value)
            :   preg_replace_callback(self::$patterns['noBinding'], function () {
                static $count = 1;
                return '$' . $count++;
            }, $value);
    }

    /**
     * Make a random string in length size
     *
     * @param int $length The length size of the string
     * @return string The random string generated
     */
    public static function randomString(int $length)
    {
        $keys = array_merge(range('a', 'z'), range('A', 'Z'));
        $key = '';
        for ($i = 0; $i < $length; $i++) {
            $key .= $keys[array_rand($keys)];
        }
        return $key;
    }

    /**
     * Detect if query is Select
     *
     * @return bool The value bound to the parameter.
     */
    public static function isSelect(string $stmt): bool
    {
        $trimMaskWithParams = "( \t\n\r\0\x0B";
        return 'SELECT' === strtoupper(substr(ltrim($stmt, $trimMaskWithParams), 0, 6));
    }
}
