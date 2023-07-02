<?php

namespace GenericDatabase\Traits;

trait YAML
{
  /**
   * Check if yaml string is valid
   *
   * @param string $yaml Argument to be tested
   * @return bool
   */
    public static function isValidYAML(string $yaml): bool
    {
        return (substr($yaml, -4) === 'yaml' && (yaml_parse_file($yaml)) ? true : false);
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
