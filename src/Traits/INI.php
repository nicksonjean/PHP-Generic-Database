<?php

namespace GenericDatabase\Traits;

trait INI
{

  /**
   * Detect if ini is valid
   * 
   * @param string $ini Argument to be tested
   * @return bool
   */
  public static function isValidINI(string $ini): bool
  {
    return (substr($ini, -3) === 'ini' && (parse_ini_file($ini)) ? true : false);
  }

  /**
   * Parse a valid ini
   * 
   * @param string $ini Argument to be parsed
   * @return array
   */
  public static function parseINI(string $ini): array
  {
    return (array) parse_ini_file($ini, false, INI_SCANNER_TYPED);
  }
}
