<?php

namespace GenericDatabase\Helpers;

/**
 * The `GenericDatabase\Helpers\Arrays` class provides a collection of static methods for manipulating arrays in PHP.
 * It includes functions for finding elements in an array, combining array indices and values, determining the type
 * of array, and more.
 *
 * Example Usage:
 * <code>
 * //Find elements in array except by keys
 * $array = ['a' => 1, 'b' => 2, 'c' => 3];
 * $keys = ['a', 'c'];
 * $result = Arrays::exceptByKeys($array, $keys);
 * </code>
 * `Output: ['b' => 2]`
 *
 * <code>
 * //Find elements in array except by values
 * $array = ['a', 'b', 'c'];
 * $values = ['b', 'c'];
 * $result = Arrays::exceptByValues($array, $values);
 * </code>
 * `Output: ['a']`
 *
 * <code>
 * //Find the first element that matches between two arrays
 * $list = ['apple', 'banana', 'cherry'];
 * $array = ['a' => 'apple', 'b' => 'banana', 'c' => 'cherry'];
 * $result = Arrays::matchValues($list, $array);
 * </code>
 * `Output: 'apple'`
 *
 * <code>
 * //Iterate the array by combining the indices and values into a new array
 * $array = ['a' => 1, 'b' => 2, 'c' => 3];
 * $result = Arrays::recombine($array);
 * </code>
 * `Output: ['a' => 1, 'b' => 2, 'c' => 3]`
 *
 * <code>
 * //Iterate through the array combining the values by substituting the indices
 * //into sequential numbers starting at zero into a new array
 * $array = ['a', 'b', 'c'];
 * $result = Arrays::assocToIndex($array);
 * </code>
 * `Output: [0 => 'a', 1 => 'b', 2 => 'c']`
 *
 * <code>
 * //Determine if array is indexed or associative
 * $array = ['a', 'b', 'c'];
 * $result = Arrays::isAssoc($array);
 * </code>
 * `Output: false`
 *
 * <code>
 * //Determine if array is multidimensional
 * $array = [['a' => 1], ['b' => 2]];
 * $result = Arrays::isMultidimensional($array);
 * </code>
 * `Output: true`
 *
 * <code>
 * //Get array values recursively
 * $array = ['a' => [1, 2], 'b' => [3, 4]];
 * $result = Arrays::arrayValuesRecursive($array);
 * </code>
 * `Output: [[1, 2], [3, 4]]`
 *
 * <code>
 * //Create an index or list array to an associative array
 * $array1 = ['a', 'b', 'c'];
 * $array2 = ['x' => 1, 'y' => 2, 'z' => 3];
 * $result = Arrays::assocToIndexCombine($array1, $array2);
 * </code>
 * `Output: [0 => 'a', 'a' => 'a', 1 => 'b', 'b' => 'b', 2 => 'c', 'c' => 'c', 'x' => 1, 'y' => 2, 'z' => 3]`
 *
 * Main functionalities:
 * - Finding elements in an array except by keys or values
 * - Finding the first element that matches between two arrays
 * - Combining array indices and values into a new array
 * - Determining if an array is indexed or associative
 * - Determining if an array is multidimensional
 * - Getting array values recursively
 * - Creating an index or list array to an associative array
 *
 * Methods:
 * - `exceptByKeys(array $array, array $keys): array`:
 * Finds elements in an array except by keys.
 * - `exceptByValues(array $array, array $values): array`:
 * Finds elements in an array except by values.
 * - `matchValues(array $list, array $array, ?string $apply = 'mb_strtolower'): string`:
 * Finds the first element that matches between two arrays.
 * - `recombine(array $array): array`:
 * Combines the indices and values of an array into a new array.
 * - `assocToIndex(array $array): array`:
 * Combines the values of an array by substituting the indices
 * into sequential numbers starting at zero into a new array.
 * - `isAssoc(mixed $array): bool`:
 * Determines if an array is indexed or associative.
 * - `isMultidimensional(array $array): bool`:
 * Determines if an array is multidimensional.
 * - `arrayValuesRecursive(array $array): array`:
 * Gets the array values recursively.
 * - `assocToIndexCombine(array ...$arrays): array`:
 * Creates an index or list array to
 *
 * @package GenericDatabase\Helpers
 */
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
    public static function matchValues(array $list, array $array, ?string $apply = 'mb_strtolower'): string
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
        if (!is_array($array) || empty($array)) {
            return false;
        }
        $firstKey = array_key_first($array);
        $lastKey = array_key_last($array);

        return $firstKey !== 0 || $lastKey !== count($array) - 1 || array_values($array) !== $array;
    }

    /**
     * Determine if array is an array multidimensional
     *
     * @param array $array The array
     * @return bool
     */
    public static function isMultidimensional(array $array): bool
    {
        return is_array($array[array_key_first($array)]);
    }

    /**
     * Determine if array is an array multidimensional
     *
     * @param array $array The array
     * @return array
     */
    public static function arrayValuesRecursive(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = array_values($value);
                self::arrayValuesRecursive($value);
            }
        }
        return $array;
    }

    /**
     * Create a numeric and incremental array, from an associative array,
     * simulating the FETCH_BOTH flag of the fetch or fetchAll method,
     * combining the associative array with the numeric array into a single array.
     *
     * @param array $arrays The array
     * @return array
     */
    public static function assocToIndexCombine(array ...$arrays): array
    {
        $data = [];
        foreach ($arrays as $array) {
            $index = 0;
            foreach ($array as $key => $value) {
                $data[$index] = $value;
                $data[$key] = $value;
                ++$index;
            }
        }
        return $data;
    }

    /**
     * This function makes an arguments list
     *
     * @param mixed $params Arguments list
     * @return array
     */
    public static function makeArgs(mixed $driver, mixed ...$params): array
    {
        $index = ['isMulti' => 2, 'isArgs' => 1];
        if ($driver === 'sqlsrv') {
            $index = ['isMulti' => 1, 'isArgs' => 0];
        }
        if (array_key_exists($index['isMulti'], $params)) {
            if (is_array($params[$index['isMulti']])) {
                $isArgs = false;
                $isArray = true;
                $isMulti = self::isMultidimensional($params[$index['isMulti']]);
                $sqlArgs = $params[$index['isMulti']];
            } else {
                $isArgs = true;
                $isArray = false;
                $isMulti = false;
                $sqlArgs = Translater::arguments($params[$index['isArgs']], array_slice($params, $index['isMulti']));
            }
        }
        return [
            'sqlStatement' => ($driver === 'sqlsrv') ? null : $params[0],
            'sqlQuery' => ($driver === 'sqlsrv') ? $params[0] : $params[1],
            'sqlArgs' => $sqlArgs ?? [],
            'isArray' => $isArray ?? false,
            'isMulti' => $isMulti ?? false,
            'isArgs' => $isArgs ?? false
        ];
    }
}
