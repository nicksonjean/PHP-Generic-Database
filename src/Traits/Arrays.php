<?php

namespace GenericDatabase\Traits;

trait Arrays
{

  /**
   * Find elements in array except by keys
   * 
   * @param array $array Array to find
   * @param array $keys Keys from array
   * @return array
   */
  public static function exceptByKeys(array $array, array $keys): array
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
   * @param array $array Array to find
   * @param array $values Values from array
   * @return array
   */
  public static function exceptByValues(array $array, array $values): array
  {
    $array = array_values(array_diff($array, $values));
    return $array;
  }

  /**
   * Find the first element that matches between two arrays
   * 
   * @param array $list A stringlist array
   * @param array $array A associative array
   * @param ?string $aplly Filter to be apply
   * @return string
   */
  public static function arrayByMatchValues(array $list, array $array, ?string $apply = 'strtolower'): string
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
