<?php

namespace GenericDatabase\Engine\PDO;

use GenericDatabase\Engine\PDOEngine;

class Attributes
{
  /**
   * static attributes constants
   * 
   */
  public static $attributeList = [
    'AUTOCOMMIT',
    'ERRMODE',
    'CASE',
    'CLIENT_VERSION',
    'CONNECTION_STATUS',
    'ORACLE_NULLS',
    'PERSISTENT',
    'PREFETCH',
    'SERVER_INFO',
    'SERVER_VERSION',
    'DRIVER_NAME',
    'TIMEOUT',
    'STRINGIFY_FETCHES',
    'EMULATE_PREPARES',
    'DEFAULT_FETCH_MODE'
  ];

  /**
   * Define all PDO attibute of the conection a ready exist
   * 
   * @return void
   */
  public static function define(): void
  {
    $result = [];
    foreach (self::$attributeList as $value) {
      $result[$value] = @trim((string) PDOEngine::getInstance()?->getAttribute(constant("PDO::ATTR_$value")));
    }
    PDOEngine::getInstance()?->setAttributes((array) $result);
  }
}
