<?php

namespace GenericDatabase\Traits;

use
  GenericDatabase\Traits\Setter,
  GenericDatabase\Traits\Getter,
  GenericDatabase\Traits\Reflections;

trait Caller
{
  use Setter, Getter, Reflections;

  /**
   * Overload and intercept not founded methods and properties
   * 
   * @param mixed $method
   * @param mixed $arguments
   * @return void
   */
  public function __call($method, $arguments)
  {
    $methodName = substr($method, 0, 3);
    $field = strtolower(substr($method, 3));
    if ($methodName == 'set') {
      $this->__set($field, ...$arguments);
      return $this;
    } elseif ($methodName == 'get') {
      return $this->__get($field);
    }
  }

  /**
   * Overload and intercept not founded methods and properties
   * 
   * @param mixed $method
   * @param mixed $arguments
   * @return void
   */
  public static function __callStatic($method, $arguments)
  {
    if (Reflections::isSingletonMethodExits(__CLASS__)) {
      $instance = Reflections::getSingletonInstance(__CLASS__);
      $instance::call($method, $arguments);
      return $instance;
    }
  }
}
