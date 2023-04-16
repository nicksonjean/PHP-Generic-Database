<?php

namespace GenericDatabase\Traits;

trait Reflections
{

  /**
   * Set default method
   */
  public static $default_method = 'getInstance';

  /**
   * Get singleton instance
   * 
   * @param mixed $field
   * @return mixed
   */
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

  /**
   * Detect if method exists in class
   * 
   * @param mixed $field
   * @return mixed
   */
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

  /**
   * Get class constants for class
   * 
   * @param mixed $field
   * @return mixed
   */
  public static function getClassConstants($class)
  {
    return (new \ReflectionClass($class))->getConstants();
  }

  /**
   * Get class constants for class by name and value
   * 
   * @param mixed $field
   * @return mixed
   */
  public static function getClassConstantName($class, $value)
  {
    return array_flip((new \ReflectionClass($class))->getConstants())[$value];
  }
}
