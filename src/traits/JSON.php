<?php
trait JSON
{
  public static function isValidJSON(string $json): bool
  {
    json_decode(file_get_contents($json));
    if (json_last_error() === JSON_ERROR_NONE) {
      return true;
    }
    return false;
  }

  public static function parseJSON(string $json): array
  {
    return (array) json_decode(file_get_contents($json), true);
  }
}
