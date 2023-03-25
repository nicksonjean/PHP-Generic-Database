<?php

namespace GenericDatabase\Engine\PgSQL;

use GenericDatabase\Traits\Reflections;

class PgSQL
{
  const ATTR_CONNECT_TIMEOUT = 1001; // dsn connect_timeout=30
  const ATTR_CONNECT_ASYNC = 1002;
  const ATTR_CONNECT_FORCE_NEW = 1003;
  const ATTR_PERSISTENT = 13; // pconnect

  // https://blog.programster.org/getting-started-with-using-postgresql-in-php
  // https://pracucci.com/php-pdo-pgsql-connection-timeout.html

  protected static $data = [];

  public static function getAttribute($name)
  {
    var_dump(self::$data);
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
