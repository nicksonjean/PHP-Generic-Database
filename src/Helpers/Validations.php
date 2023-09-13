<?php

namespace GenericDatabase\Helpers;

class Validations
{
    /**
     * Check if a value is numeric
     *
     * @param string $value Value to be checked
     * @return bool True if the value is numeric, false otherwise
     */
    public static function isNumber(string $value): bool
    {
        return is_numeric($value);
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
     * Make a random string in length size
     *
     * @param int $length The length size of the string
     * @return string The random string generated
     */
    public static function randomString(int $length): string
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
        return 'SELECT' === mb_strtoupper(substr(ltrim($stmt, $trimMaskWithParams), 0, 6));
    }
}
