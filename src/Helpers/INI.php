<?php

namespace GenericDatabase\Helpers;

/**
 * The `GenericDatabase\Helpers\INI` class provides methods for working with INI files.
 * It includes a method to check if a given argument is a valid INI file and a method
 * to parse a valid INI file and return its contents as an array.
 *
 * Example Usage:
 * <code>
 * // Check if a file is a valid INI file
 * $isValid = INI::isValidINI('config.ini');
 * </code>
 * `Output: true`
 *
 * <code>
 * // Parse an INI file and retrieve its contents as an array
 * $contents = INI::parseINI('config.ini');
 * </code>
 * `Output: ['key1' => 'value1', 'key2' => 'value2']`
 *
 * Main functionalities:
 * - Check if a given argument is a valid INI file
 * - Parse a valid INI file and return its contents as an array
 *
 * Methods:
 * - `isValidINI($ini)`:
 * Checks if a given argument is a valid INI file. Returns true if the argument is a valid INI file, false otherwise.
 * - `parseINI($ini)`:
 * Parses a valid INI file and returns its contents as an array.
 *
 * @package GenericDatabase\Helpers
 */
class INI
{
    /**
     * Check if a given argument is a valid INI file.
     *
     * @param mixed $ini The argument to be tested.
     * @return bool True if the argument is a valid INI file, false otherwise.
     */
    public static function isValidINI(mixed $ini): bool
    {
        if (!is_string($ini)) {
            return false;
        }

        return str_ends_with($ini, '.ini') && parse_ini_file($ini) !== false;
    }

    /**
     * Parse a valid INI file and return its contents as an array.
     *
     * @param string $ini The INI file to be parsed.
     * @return array The contents of the INI file as an array.
     */
    public static function parseINI(string $ini): array
    {
        return (array) parse_ini_file($ini, false, INI_SCANNER_TYPED);
    }
}
