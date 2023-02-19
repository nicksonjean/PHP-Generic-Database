<?php
trait Arrays
{
  public static function exceptByKeys($array, $keys)
  {
    foreach ($keys as $key) {
      unset($array[$key]);
    }
    $array = array_values($array);
    return $array;
  }

  public static function exceptByValues($array, $values)
  {
    $array = array_values(array_diff($array, $values));
    return $array;
  }
}
