<?php

namespace GenericDatabase\Traits;

trait INI
{
  public static function isValidINI(string $ini): bool
  {
    return (substr($ini, -3) === 'ini' && (parse_ini_file($ini)) ? true : false);
  }

  public static function parseINI(string $ini): array
  {
    return (array) parse_ini_file($ini);
  }
}
