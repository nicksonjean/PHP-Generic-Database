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

  /**
   * Find elements in array except by values
   * 
   * @param $list
   * @param $array
   * @param $aplly
   * @return array
   */
  public static function arrayByMatchValues($list, $array, $apply = 'strtolower'): string
  {
    $engine = array_map(
      'unserialize',
      array_intersect(
        array_map('serialize', array_map($apply, $list)),
        array_map('serialize', $array)
      )
    );

    return $list[array_key_first($engine)];
  }
}
