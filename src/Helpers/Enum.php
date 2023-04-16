<?php

namespace GenericDatabase\Helpers;

class Enum
{

  protected static $_constantToClassMap = array();
  protected static function who()
  {
    return __CLASS__;
  }

  public static function registerConstants($constants)
  {
    $class = static::who();
    foreach ($constants as $name => $value) {
      self::$_constantToClassMap[$class . '_' . $name] = new $class();
    }
  }

  public static function __callStatic($name, $arguments)
  {
    return self::$_constantToClassMap[static::who() . '_' . $name];
  }
}
