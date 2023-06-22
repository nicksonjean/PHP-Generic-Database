<?php

namespace GenericDatabase\Traits;

trait Regex
{
  /**
   * Regex pattern for only numbers
   */
  private static $regex = [
    'onlyNumbers' => "/^(?:-(?:[1-9](?:\\d{0,2}(?:,\\d{3})+|\\d*))|(?:0|(?:[1-9](?:\\d{0,2}(?:,\\d{3})+|\\d*))))(?:.\\d+|)$/"
  ];

  /**
   * Check if a value is numeric
   * 
   * @param mixed $value Value to be checked
   * @return int | false True if the value is numeric, false otherwise
   */
  public static function isNumber(string $value): int | false
  {
    return preg_match(self::$regex['onlyNumbers'], (string) $value);
  }
  /** 
   * Check "Booleanic" Conditions :)
   *
   * @param mixed $variable Can be anything (string, bol, integer, etc.)
   * @return mixed Returns TRUE  for "1", "true", "on" and "yes", Returns FALSE for "0", "false", "off" and "no", Returns NULL otherwise.
   */
  public static function isBoolean($value): mixed
  {
    if (!isset($value)) return null;
    return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
  }
}
