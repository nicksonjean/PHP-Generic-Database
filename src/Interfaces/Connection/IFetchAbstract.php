<?php

namespace GenericDatabase\Interfaces\Connection;

use ReflectionException;

/**
 * This interface defines the contract for fetching data from a database.
 * Implementing classes should provide concrete implementations for the methods
 * declared in this interface to handle data retrieval operations.
 *
 * @package PHP-Generic-Database\Interfaces\Connection
 * @subpackage IFetchAbstract
 */
interface IFetchAbstract
{
    /**
     * Fetches a single row from the result set, or false if there are no more rows.
     *
     * @param array|null $constructorArguments Arguments to pass to the class constructor.
     * @param string|null $aClassOrObject Class name or object to hydrate.
     * @return object|false
     * @throws ReflectionException
     */
    public function internalFetchClass(?array $constructorArguments = null, ?string $aClassOrObject = '\stdClass'): object|false;

    /**
     * Fetches the current row as both an associative and a numeric array.
     *
     * @return bool|array Returns an associative and numeric array if successful, false on failure.
     */
    public function internalFetchBoth(): bool|array;

    /**
     * Fetches a single row from the result set as a numerically indexed array,
     * converting all values to strings, or returns false if there are no more rows.
     *
     * @return array|false|null A numerically indexed array of the row values as strings, false if no more rows, or null on error.
     */
    public function internalFetchAssoc(): array|null|false;

    /**
     * Fetches the current row as a numeric array.
     *
     * @return array|false|null Returns a numeric array if successful, null if no row was found, or false on failure.
     */
    public function internalFetchNum(): array|false|null;

    /**
     * Fetches a single value from the result set, or false if there are no more rows.
     *
     * @param int $columnIndex Index of the value to fetch. If not provided, fetches the first column.
     * @return string|false The value of the row at the specified index, false if no more rows, or null on error.
     */
    public function internalFetchColumn(int $columnIndex = 0): false|string;

    /**
     * Fetches all rows from the result set as an associative array, or an empty array if there are no more rows.
     *
     * @return array An associative array of the row values as strings, or an empty array if no more rows, or null on error.
     */
    public function internalFetchAllAssoc(): array;

    /**
     * Fetches all rows from the result set as a numerically indexed array of arrays,
     * converting all values to strings, or an empty array if there are no more rows.
     *
     * @return array A numerically indexed array of the row values as strings, or an empty array if no more rows, or null on error.
     */
    public function internalFetchAllNum(): array;

    /**
     * Fetches all rows from the result set as an array of arrays, where each row is both numerically and associatively indexed.
     * All values are converted to strings. If there are no more rows, an empty array is returned.
     *
     * @return array An array of the row values as strings, with both numerical and associative indexes, or an empty array if no more rows, or null on error.
     */
    public function internalFetchAllBoth(): array;

    /**
     * Fetches all values of a single column from the result set as an array of strings.
     *
     * @param int $columnIndex Index of the value to fetch. If not provided, fetches the first column.
     * @return array An array of the column values as strings, or an empty array if no more rows, or null on error.
     */
    public function internalFetchAllColumn(int $columnIndex = 0): array;

    /**
     * Fetches all rows from the result set as an array of objects, each one being an instance of the provided class.
     * The class is created with the provided constructor arguments and properties are set with the values from the row.
     * If there are no more rows, an empty array is returned.
     *
     * @param array|null $constructorArguments Arguments to pass to the class constructor.
     * @param string|null $aClassOrObject Class name or object to hydrate.
     * @return array An array of the row values as objects, or an empty array if no more rows, or null on error.
     * @throws ReflectionException
     */
    public function internalFetchAllClass(?array $constructorArguments = [], ?string $aClassOrObject = '\stdClass'): array;
}

