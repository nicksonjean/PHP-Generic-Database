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

        // Must be a .yaml or .yml file
        if (!str_ends_with($yaml, '.yaml') && !str_ends_with($yaml, '.yml')) {
            return false;
        }

        // File must exist
        if (!file_exists($yaml)) {
            return false;
        }

        $result = yaml_parse_file($yaml);
        return $result !== false;
    }

    /**
     * Parse YAML content or file
     * Validates whether to use yaml_parse_file or yaml_parse based on input type
     *
     * @param string $yamlInput Either a file path (.yaml/.yml) or YAML string content
     * @return array Parsed YAML data as array
     */
    public static function parseYaml(string $yamlInput): array
    {
        // Determine if input is a file path or content
        $isFilePath = (str_ends_with($yamlInput, '.yaml') || str_ends_with($yamlInput, '.yml')) && file_exists($yamlInput);

        if (extension_loaded('yaml')) {
            $result = $isFilePath ? yaml_parse_file($yamlInput) : yaml_parse($yamlInput);
            return is_array($result) ? $result : [];
        }

        if (class_exists('Symfony\Component\Yaml\Yaml')) {
            $result = $isFilePath
                ? \Symfony\Component\Yaml\Yaml::parseFile($yamlInput)
                : \Symfony\Component\Yaml\Yaml::parse($yamlInput);
            return is_array($result) ? $result : [];
        }

        return self::basicYamlParse($yamlInput);
    }

    /**
     * Emit (convert) array data to YAML format
     *
     * @param array $data Data to be converted to YAML
     * @param int $inlineLevel Inline level (default: 2)
     * @param int $indentation Indentation spaces (default: 2)
     * @return string YAML formatted string
     */
    public static function emitYaml(array $data, int $inlineLevel = 2, int $indentation = 2): string
    {
        if (extension_loaded('yaml')) {
            return yaml_emit($data, YAML_UTF8_ENCODING);
        }

        if (class_exists('Symfony\Component\Yaml\Yaml')) {
            return \Symfony\Component\Yaml\Yaml::dump($data, $inlineLevel, $indentation);
        }

        return self::basicYamlEmit($data, $indentation);
    }

    /**
     * Basic YAML parser implementation (fallback when extensions are not available)
     *
     * @param string $content YAML content or file path
     * @return array Parsed data as array
     */
    public static function basicYamlParse(string $content): array
    {
        // If it's a file path, read the file content
        if ((str_ends_with($content, '.yaml') || str_ends_with($content, '.yml')) && file_exists($content)) {
            $content = file_get_contents($content);
        }

        $result = [];
        $lines = explode("\n", $content);
        $currentItem = [];
        $inItem = false;

        foreach ($lines as $line) {
            $line = rtrim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;

            if (str_starts_with($line, '- ')) {
                if (!empty($currentItem)) {
                    $result[] = $currentItem;
                }
                $currentItem = [];
                $inItem = true;

                $rest = trim(substr($line, 2));
                if (!empty($rest) && str_contains($rest, ':')) {
                    [$key, $value] = explode(':', $rest, 2);
                    $currentItem[trim($key)] = trim($value, " \t\"'");
                }
            } elseif ($inItem && preg_match('/^\s+(\w+):\s*(.*)$/', $line, $matches)) {
                $currentItem[trim($matches[1])] = trim($matches[2], " \t\"'");
            }
        }

        if (!empty($currentItem)) {
            $result[] = $currentItem;
        }

        return $result;
    }

    /**
     * Basic YAML emitter implementation (fallback when extensions are not available)
     *
     * @param array $data Data to be converted to YAML
     * @param int $indentation Indentation spaces (default: 2)
     * @return string YAML formatted string
     */
    public static function basicYamlEmit(array $data, int $indentation = 2): string
    {
        $output = "";
        foreach ($data as $row) {
            $output .= "-\n";
            foreach ((array) $row as $key => $value) {
                $val = is_null($value) ? 'null' : (is_bool($value) ? ($value ? 'true' : 'false') : (string) $value);
                if (is_string($value) && (str_contains($value, ':') || str_contains($value, '#'))) {
                    $val = '"' . addslashes($value) . '"';
                }
                $output .= str_repeat(' ', $indentation) . "$key: $val\n";
            }
        }
        return $output;
    }
}
