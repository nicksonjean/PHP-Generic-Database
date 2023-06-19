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
   * @param mixed $class
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
   * @param mixed $class
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
   * @param mixed $class
   * @return mixed
   */
  public static function getClassConstants($class)
  {
    return (new \ReflectionClass($class))->getConstants();
  }

  /**
   * Get class constants for class by name and value
   * 
   * @param mixed $class
   * @param mixed $field
   * @return mixed
   */
  public static function getClassConstantName($class, $field)
  {
    return array_flip((new \ReflectionClass($class))->getConstants())[$field];
  }

  /**
   * Get class properby for class by name
   * 
   * @param mixed $class 
   * @param mixed $prop
   * @return mixed
   */
  public static function getClassPropertyName($class, $prop)
  {
    $reflection = new \ReflectionClass($class);
    $property = $reflection->getProperty($prop);
    $property->setAccessible(true);
    return $property->getValue(null);
  }
}
