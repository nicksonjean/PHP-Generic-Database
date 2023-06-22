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
   * @param mixed $class The class object or instance
   * @return mixed
   */
  public static function getSingletonInstance($class): mixed
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
   * @param mixed $class The class object or instance
   * @return mixed
   */
  public static function isSingletonMethodExits($class): mixed
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
   * @param mixed $class The class object or instance
   * @return mixed
   */
  public static function getClassConstants($class): mixed
  {
    return (new \ReflectionClass($class))->getConstants();
  }

  /**
   * Get class constants for class by name and value
   * 
   * @param mixed $class The class object or instance
   * @param mixed $field Get the constant name data
   * @return mixed
   */
  public static function getClassConstantName($class, $field): mixed
  {
    return array_flip((new \ReflectionClass($class))->getConstants())[$field];
  }

  /**
   * Get class properby for class by name
   * 
   * @param mixed $class The class object or instance
   * @param mixed $prop Get the property name data
   * @return mixed
   */
  public static function getClassPropertyName($class, $prop): mixed
  {
    $reflection = new \ReflectionClass($class);
    $property = $reflection->getProperty($prop);
    $property->setAccessible(true);
    return $property->getValue(null);
  }
}
