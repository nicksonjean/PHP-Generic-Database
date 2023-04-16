<?php

namespace GenericDatabase\Engine\MySQLi;

use GenericDatabase\Traits\Reflections;

class MySQL
{
  const ATTR_OPT_CONNECT_TIMEOUT = 2;
  const ATTR_OPT_READ_TIMEOUT = 3;
  const ATTR_OPT_LOCAL_INFILE = 1003;
  const ATTR_INIT_COMMAND = 1002;
  const ATTR_SET_CHARSET_NAME = 4;
  const ATTR_READ_DEFAULT_FILE = 1006;
  const ATTR_READ_DEFAULT_GROUP = 1007;
  const ATTR_SERVER_PUBLIC_KEY = 1008;
  const ATTR_OPT_NET_CMD_BUFFER_SIZE = 1009;
  const ATTR_OPT_NET_READ_BUFFER_SIZE = 1010;
  const ATTR_OPT_INT_AND_FLOAT_NATIVE = 1011;
  const ATTR_OPT_SSL_VERIFY_SERVER_CERT = 1012;
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
