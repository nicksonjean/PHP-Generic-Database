<?php

namespace GenericDatabase\Traits;

trait YAML
{
  public static function isValidYAML(string $yaml): bool
  {
    return (substr($yaml, -4) === 'yaml' && (yaml_parse_file($yaml)) ? true : false);
  }

  public static function parseYAML(string $yaml): array
  {
    return (array) yaml_parse_file($yaml);
  }
}
