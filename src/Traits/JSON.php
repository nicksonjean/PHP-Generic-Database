<?php

namespace GenericDatabase\Traits;

trait JSON
{

  /**
   * Detect if json is valid
   * 
   * @param string $json Argument to be tested
   * @return bool
   */
  public static function isValidJSON(string $json): bool
  {
    json_decode(file_get_contents($json));
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
    return (array) json_decode(file_get_contents($json), true);
  }
}
