<?php

namespace GenericDatabase;

class PDOAttributes
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
   * Retrieve all PDO attibute of the conection a ready exist
   * 
   * @return PDO attribute list array
   */
  public static function fetchAll(): array
  {
    $result = [];
    foreach (self::$attributeList as $value) {
      $result[$value] = @trim((string) PDOEngine::getInstance()?->getAttribute(constant("PDO::ATTR_$value")));
    }
    PDOEngine::getInstance()?->setAttributes((array) $result);
    return $result;
  }
}
