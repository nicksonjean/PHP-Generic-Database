<?php

namespace GenericDatabase\Engine\SQLite3;

use GenericDatabase\Traits\Reflections;

class SQLite
{
  const ATTR_OPEN_READONLY = 1;
  const ATTR_OPEN_READWRITE = 2;
  const ATTR_OPEN_CREATE = 4;
  const ATTR_CONNECT_TIMEOUT = 12;
  const ATTR_PERSISTENT = 13;
  const ATTR_AUTOCOMMIT = 14;

  protected static $data = [];

  public static function getAttribute($name)
  {
    if (isset(self::$data[$name])) {
      if (is_int($name)) {
        $result = self::$data[Reflections::getClassConstantName(__CLASS__, $name)];
      } else {
        $result = self::$data[$name];
      }
    } else {
      $result = null;
    }
    return $result;
  }

  public static function setAttribute($name, $value)
  {
    if (is_null($name)) {
      self::$data[] = $value;
    } else if (is_int($name)) {
      self::$data[Reflections::getClassConstantName(__CLASS__, $name)] = $value;
    } else {
      self::$data[$name] = $value;
    }
  }
}
