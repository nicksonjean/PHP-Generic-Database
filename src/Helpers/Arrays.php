<?php

namespace GenericDatabase\Helpers;

class Arrays
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
        return array_diff_key($array, array_flip($keys));
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
        return array_values(array_diff($array, $values));
    }

    /**
     * Find the first element that matches between two arrays
     *
     * @param array $list A string list array
     * @param array $array An associative array
     * @param string|null $apply Filter to be applied
     * @return string
     */
    public static function matchValues(array $list, array $array, ?string $apply = 'strtolower'): string
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

    /**
     * Iterate the array by combining the indices and values into a new array
     *
     * @param array $array The array to combine
     * @return array
     */
    public static function recombine(array $array): array
    {
        return array_combine(array_keys($array), array_values($array));
    }

    /**
     * Iterates through the array combining the values by substituting
     * the indices into sequential numbers starting at zero into a new array
     *
     * @param array $array The array to combine
     * @return array
     */
    public static function assocToIndex(array $array): array
    {
        return array_combine(range(0, count($array) - 1), array_values($array));
    }

    /**
     * Determine if array is indexed or associative
     *
     * @param mixed $array The array
     * @return bool
     */
    public static function isAssoc(mixed $array): bool
    {
        if (false === is_array($array) || 0 === ($count = count($array))) {
            return false;
        }

        reset($array);
        if (key($array) !== 0) {
            return true;
        }

        end($array);
        if (key($array) !== $count - 1) {
            return true;
        }

        return array_values($array) !== $array;
    }
}