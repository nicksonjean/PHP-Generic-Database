<?php

namespace GenericDatabase\Helpers\Parsers;

/**
 * The `GenericDatabase\Helpers\Parsers\JSON` class provides two static methods for working with JSON data.
 * The isValidJSON method checks if a given string is a valid JSON, and the parseJSON method parses
 * a valid JSON string into an array.
 *
 * Example Usage:
 * <code>
 * // Parse an INI file and retrieve its contents as an array
 * $json = '{"name": "John", "age": 30}';
 * $isValid = JSON::isValidJSON($json);
 * </code>
 * `Output: $isValid is being true`
 *
 * <code>
 * // Parse a valid JSON string into an array
 * $json = '{"name": "John", "age": 30}';
 * $data = JSON::parseJSON($json);
 * </code>
 * `Output: $data will be an array containing the parsed JSON data`
 *
 * Main functionalities:
 * - Check if a string is a valid JSON
 * - Parse a valid JSON string into an array
 *
 * Methods:
 * - `isValidJSON($json)`: Checks if a given string is a valid JSON. Returns a boolean value indicating the result.
 * - `parseJSON($json)`: Parses a valid JSON string into an array. Returns the parsed JSON data as an array.
 *
 * @package GenericDatabase\Helpers\Parsers
 * @subpackage JSON
 */
class JSON
{
    /**
     * Detect if json is valid
     *
     * @param mixed $json Argument to be tested
     * @return bool
     */
    public static function isValidJSON(mixed $json): bool
    {
        if (!is_string($json)) {
            return false;
        }

        // Check if it's a directory - directories cannot be valid JSON
        if (is_dir($json)) {
            return false;
        }

        set_error_handler(fn(): bool => true, E_WARNING);
        json_decode(file_get_contents($json));
        restore_error_handler();
        if (json_last_error() === JSON_ERROR_NONE) {
            return true;
        }
        return false;
    }

    /**
     * Parse a valid json
     *
     * @param string $json Argument to be parsed
     * @return array
     */
    public static function parseJSON(string $json): array
    {
        // Check if it's a directory - directories cannot be parsed as JSON
        if (is_dir($json)) {
            return [];
        }

        return (array) json_decode(file_get_contents($json), true);
    }
}

