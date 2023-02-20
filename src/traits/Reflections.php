<?php

namespace GenericDatabase\Traits;

trait Reflections
{
  public static $default_method = 'getInstance';

  public static function getSingletonInstance($class)
  {
    try {
      $result = call_user_func($class . '::' . self::$default_method);
    } catch (\Exception $e) {
      $message = sprintf('Method %s not founded in the class %s', [self::$default_method, $class]);
      throw new \Exception($message);
    }
    return $result;
  }

  public static function isSingletonMethodExits($class)
  {
    try {
      $rm = new \ReflectionMethod($class, self::$default_method);
      $result = ($rm->isStatic()) ? true : false;
    } catch (\Exception $e) {
      $message = sprintf('Method %s not founded in the class %s', [self::$default_method, $class]);
      throw new \Exception($message);
    }
    return $result;
  }
}
