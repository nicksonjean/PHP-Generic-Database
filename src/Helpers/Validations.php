<?php

namespace GenericDatabase\Helpers;

/**
 * The `GenericDatabase\Helpers\Validations` class provides several static methods for performing common validation
 * tasks. These methods include checking if a value is numeric, checking "Booleanic" conditions, generating random
 * strings, and detecting if a query is a SELECT statement.
 *
 * Example Usage:
 * <code>
 * // Check if a value is numeric
 * $value = '123';
 * $isNumber = Validations::isNumber($value);
 * echo $isNumber ? 'Numeric' : 'Not numeric';
 * </code>
 * `Output: Numeric`
 *
 * <code>
 * // Check "Booleanic" conditions
 * $value = 'true';
 * $isBoolean = Validations::isBoolean($value);
 * echo $isBoolean ? 'True' : 'False';
 * </code>
 * `Output: True`
 *
 * <code>
 * // Generate a random string
 * $length = 10;
 * $randomString = Validations::randomString($length);
 * echo $randomString;
 * </code>
 * `Output: Random string of length 10`
 *
 * <code>
 * // Detect if a query is a SELECT statement
 * $stmt = 'SELECT * FROM table';
 * $isSelect = Validations::isSelect($stmt);
 * echo $isSelect ? 'SELECT statement' : 'Not a SELECT statement';
 * </code>
 * `Output: SELECT statement`
 *
 * Main functionalities:
 * - The `isNumber` method checks if a value is numeric by using the is_numeric function.
 * - The `isBoolean` method checks "Booleanic" conditions
 * by using the `filter_var` function with the `FILTER_VALIDATE_BOOLEAN` flag.
 * - The `randomString` method generates a random string of a specified length by selecting
 * random characters from an array of letters.
 * - The `isSelect` method detects if a query is a SELECT statement by checking if the query
 * starts with the word "SELECT" (case-insensitive).
 *
 * Methods:
 * - `isNumber(string $value): bool`: Checks if a value is numeric.
 * - `isBoolean(mixed $value): mixed`: Checks "Booleanic" conditions.
 * - `randomString(int $length): string`: Generates a random string of a specified length.
 * - `isSelect(string $stmt): bool`: Detects if a query is a SELECT statement.
 *
 * @package GenericDatabase\Helpers
 */
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
