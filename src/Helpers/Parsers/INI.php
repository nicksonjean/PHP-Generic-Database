<?php

namespace GenericDatabase\Helpers\Parsers;

/**
 * The `GenericDatabase\Helpers\Parsers\INI` class provides methods for working with INI files.
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
 * - `isValidINI($ini)`: Checks if a given argument is a valid INI file. Returns true if the argument is a valid INI file, false otherwise.
 * - `parseINI($ini)`: Parses a valid INI file and returns its contents as an array.
 *
 * @package GenericDatabase\Helpers\Parsers
 * @subpackage INI
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
        return (array) parse_ini_file($ini, false, INI_SCANNER_NORMAL);
    }

    /**
     * Parse an INI file and return its contents as an associative array.
     *
     * @param string $filepath The path to the INI file.
     * @return array The contents of the INI file as an associative array.
     */
    public static function parseIniFile(string $filepath): array
    {
        $ini = file($filepath);
        $result = [];
        $section = '';

        foreach ($ini as $line) {
            $line = trim($line);

            if ($line === '' || $line[0] === ';') {
                continue;
            }

            if ($line[0] === '[') {
                $section = trim($line, "[]");
                continue;
            }

            if (str_contains($line, '=')) {
                [$key, $value] = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);

                if ($section === '') {
                    $result[$key] = $value;
                } else {
                    $result[$section][$key] = $value;
                }
            }
        }

        return $result;
    }

    /**
     * Parse INI string content (tabular format with [row_N] sections) into array of rows.
     * Compatible with JSON/CSV-like structure: array of associative arrays.
     * Uses INI_SCANNER_RAW to avoid "unexpected BOOL_FALSE" when values match reserved
     * keywords (yes, no, true, false, on, off, null, none). Type coercion is applied by Schema.
     *
     * @param string $content The INI string content.
     * @param int $scannerMode INI_SCANNER_RAW (default), INI_SCANNER_NORMAL or INI_SCANNER_TYPED.
     * @return array Array of rows (associative arrays).
     */
    public static function parseTableIniString(string $content, int $scannerMode = INI_SCANNER_RAW): array
    {
        $content = trim($content);
        if ($content === '') {
            return [];
        }

        $parsed = parse_ini_string($content, true, $scannerMode);
        if ($parsed === false || !is_array($parsed)) {
            return [];
        }
        // Sections become rows; preserve order via array_values
        return array_values(array_filter($parsed, 'is_array'));
    }

    /**
     * Encode array of rows to INI format (tabular with [row_N] sections).
     * PHP has no native ini_encode; this provides the conversion.
     *
     * @param array $data Array of rows (associative arrays).
     * @param int $flags Reserved for future use.
     * @return string INI format string, or empty string on invalid input.
     */
    public static function encodeTableToIni(array $data, int $flags = 0): string
    {
        $lines = [];
        $idx = 1;
        foreach ($data as $row) {
            if (!is_array($row) && !$row instanceof \stdClass) {
                continue;
            }
            $lines[] = '[row_' . $idx . ']';
            foreach ((array) $row as $key => $value) {
                if ($value === null) {
                    $value = '';
                }
                $value = (string) $value;
                $lines[] = $key . ' = ' . $value;
            }
            $lines[] = '';
            $idx++;
        }
        return implode("\n", $lines);
    }
}
