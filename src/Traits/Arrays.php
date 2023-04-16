<?php

namespace GenericDatabase\Traits;

trait Arrays
{

  /**
   * Find elements in array except by keys
   * 
   * @param $array
   * @param $keys
   * @return array
   */
  public static function exceptByKeys($array, $keys): array
  {
    foreach ($keys as $key) {
      unset($array[$key]);
    }
    $array = array_values($array);
    return $array;
  }

  /**
   * Find elements in array except by values
   * 
   * @param $array
   * @param $keys
   * @return array
   */
  public static function exceptByValues($array, $values): array
  {
    $array = array_values(array_diff($array, $values));
    return $array;
  }
}
