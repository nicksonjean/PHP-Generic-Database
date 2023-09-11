<?php

namespace GenericDatabase\Helpers;

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
