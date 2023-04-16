<?php

namespace GenericDatabase\Traits;

trait YAML
{

  /**
   * Detect if yaml is valid
   * 
   * @param string $yaml
   * @return bool
   */
  public static function isValidYAML(string $yaml): bool
  {
    return (substr($yaml, -4) === 'yaml' && (yaml_parse_file($yaml)) ? true : false);
  }

  /**
   * Parse a valid yaml
   * 
   * @param string $yaml
   * @return array
   */
  public static function parseYAML(string $yaml): array
  {
    return (array) yaml_parse_file($yaml);
  }
}
