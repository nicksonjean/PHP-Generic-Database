<?php

namespace GenericDatabase\Engine\SQLSrv;

use GenericDatabase\Traits\Reflections;

class SQLSrv
{
  const ATTR_CONNECT_TIMEOUT = 1001;
  const ATTR_PERSISTENT = 13;

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
