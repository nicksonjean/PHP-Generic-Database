<?php

namespace GenericDatabase\Helpers\Parsers;

/**
 * The `GenericDatabase\Helpers\Parsers\YAML` class provides validation and parsing functionalities for YAML strings.
 * It contains two static methods: isValidYAML and parseYAML.
 *
 * Example Usage:
 * <code>
 * // Validate a YAML string
 * $yaml = 'example.yaml';
 * $isValidYAML = \GenericDatabase\Helpers\YAML::isValidYAML($yaml);
 * </code>
 * `$isValidYAML will be true if the YAML string is valid, false otherwise`
 *
 * <code>
 * // Parse a YAML string
 * $value = 'true';
 * $yaml = 'example.yaml';
 * $parsedYAML = \GenericDatabase\Helpers\YAML::parseYAML($yaml);
 * </code>
 * `$parsedYAML will be an array containing the parsed YAML data`
 *
 * Main functionalities:
 * - The `isValidYAML` method checks if a YAML string is valid by verifying that it is a string and ends with the extension '.yaml'. It also uses the `yaml_parse_file` function to further validate the YAML syntax.
 * - The `parseYAML` method parses a valid YAML string by using the `yaml_parse_file` function and returns the parsed data as an array.
 *
 * Methods:
 * - `isValidYAML(mixed $yaml): bool`: Checks if a YAML string is valid.
 * - `parseYAML(string $yaml): array`: Parses a valid YAML string and returns the parsed data as an array.
 *
 * @package GenericDatabase\Helpers\Parsers
 * @subpackage YAML
 */
class YAML
{
    /**
     * Check if yaml string is valid
     *
     * @param mixed $yaml Argument to be tested
     * @return bool
     */
    public static function isValidYAML(mixed $yaml): bool
    {
        if (!is_string($yaml)) {
            return false;
        }
        return str_ends_with($yaml, 'yaml') && (yaml_parse_file($yaml));
    }

    /**
     * Parse a valid yaml string
     *
     * @param string $yaml Argument to be parsed
     * @return array
     */
    public static function parseYAML(string $yaml): array
    {
        return (array) yaml_parse_file($yaml);
    }
}
