<?php

namespace GenericDatabase\Interfaces\Connection;

interface IFetchs
{
    /**
     * Fetches the current row as both an associative and a numeric array.
     *
     * @param mixed $statement The database statement to fetch the row from.
     * @return bool|array Returns an associative and numeric array if successful, false on failure.
     */
    public static function internalFetchBoth($statement = null): bool|array;

    /**
     * Fetches the current row as an associative array.
     *
     * @param mixed $statement The database statement to fetch the row from.
     * @return array|null|false Returns an associative array if successful, null if no row was found, or false on failure.
     */
    public static function internalFetchAssoc(mixed $statement): array|null|false;

    /**
     * Fetches the current row as a numeric array.
     *
     * @param mixed $statement The database statement to fetch the row from.
     * @return bool|array|null Returns a numeric array if successful, null if no row was found, or false on failure.
     */
    public static function internalFetchNum($statement = null): bool|array|null;

    public static function internalFetchColumn($statement = null, $columnIndex = 0);

    public static function internalFetchAllAssoc($statement = null): array;

    public static function internalFetchAllNum($statement = null): array;

    public static function internalFetchAllBoth($statement = null): array;

    public static function internalFetchAllColumn($statement = null, $columnIndex = 0): array;

    public static function internalFetchAllClass($statement = null, $constructorArguments = [], $aClassOrObject = '\stdClass'): array;
}
