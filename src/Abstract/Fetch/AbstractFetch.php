<?php

namespace GenericDatabase\Abstract\Fetch;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Fetch\Strategy\IFetchStrategy;
use GenericDatabase\Helpers\Reflections;
use ReflectionException;

/**
 * Abstract base class implementing the template methods for fetching
 */
abstract class AbstractFetch
{
    protected IFetchStrategy $fetchStrategy;
    protected static IConnection $connection;

    public function __construct(IConnection $connection, IFetchStrategy $fetchStrategy)
    {
        $this->fetchStrategy = $fetchStrategy;
        self::$connection = $connection;
    }

    public function getStrategy(): IFetchStrategy
    {
        return $this->fetchStrategy;
    }

    public function getInstance(): IConnection
    {
        return self::$connection;
    }

    /**
     * Fetches a single row from the result set, or false if there are no more rows.
     *
     * @param array|null $constructorArguments Arguments to pass to the class constructor.
     * @param string|null $aClassOrObject Class name or object to hydrate.
     * @return object|false
     * @throws ReflectionException
     */
    public function internalFetchClass(
        ?array $constructorArguments = null,
        ?string $aClassOrObject = '\stdClass'
    ): object|false {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return false;
        }
        $cacheData = $this->getStrategy()->cacheResults($statement);
        $results = $cacheData['results'];

        if (isset($results[$cacheData['position']])) {
            $row = $results[$cacheData['position']++];
            return Reflections::createObjectAndSetPropertiesCaseInsensitive(
                $aClassOrObject,
                $constructorArguments ?? [],
                $row
            );
        }

        return false;
    }

    /**
     * Fetches a single row from the result set as an array, or false if there are no more rows.
     *
     * @return array|false
     */
    public function internalFetchBoth(): bool|array
    {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return false;
        }
        $cacheData = $this->getStrategy()->cacheResults($statement);
        $results = $cacheData['results'];

        if (isset($results[$cacheData['position']])) {
            $row = $results[$cacheData['position']++];
            $result = [];
            $index = 0;
            foreach ($row as $key => $value) {
                $result[$index] = (string) $value;
                $result[$key] = (string) $value;
                $index++;
            }
            return $result;
        }

        return false;
    }

    /**
     * Fetches a single row from the result set as an associative array, or false if there are no more rows.
     *
     * @return array|false|null
     */
    public function internalFetchAssoc(): array|null|false
    {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return false;
        }
        $cacheData = $this->getStrategy()->cacheResults($statement);
        $results = $cacheData['results'];

        if (isset($results[$cacheData['position']])) {
            return $results[$cacheData['position']++];
        }

        return false;
    }

    /**
     * Fetches a single row from the result set as a numerically indexed array,
     * converting all values to strings, or returns false if there are no more rows.
     *
     * @return array|false|null A numerically indexed array of the row values as strings, false if no more rows, or null on error.
     */
    public function internalFetchNum(): array|false|null
    {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return false;
        }
        $cacheData = $this->getStrategy()->cacheResults($statement);
        $results = $cacheData['results'];

        if (isset($results[$cacheData['position']])) {
            $row = array_values($results[$cacheData['position']++]);
            return array_map('strval', $row);
        }

        return false;
    }

    /**
     * Fetches a single value from the result set, or false if there are no more rows.
     *
     * @param int $columnIndex Index of the value to fetch. If not provided, fetches the first column.
     * @return string|false The value of the row at the specified index, false if no more rows, or null on error.
     */
    public function internalFetchColumn(int $columnIndex = 0): false|string
    {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return false;
        }

        $this->getStrategy()->handleFetchReset($statement);
        $cacheData = $this->getStrategy()->cacheResults($statement);
        $results = $cacheData['results'];

        if (isset($results[$cacheData['position']])) {
            $row = $results[$cacheData['position']++];
            $values = array_values($row);
            return isset($values[$columnIndex]) ? (string) $values[$columnIndex] : false;
        }

        return false;
    }

    /**
     * Fetches all rows from the result set as an associative array, or an empty array if there are no more rows.
     *
     * @return array An associative array of the row values as strings, or an empty array if no more rows, or null on error.
     */
    public function internalFetchAllAssoc(): array
    {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return [];
        }
        $cacheData = $this->getStrategy()->cacheResults($statement);
        return $cacheData['results'];
    }

    /**
     * Fetches all rows from the result set as a numerically indexed array of arrays,
     * converting all values to strings, or an empty array if there are no more rows.
     *
     * @return array A numerically indexed array of the row values as strings, or an empty array if no more rows, or null on error.
     */
    public function internalFetchAllNum(): array
    {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return [];
        }
        $cacheData = $this->getStrategy()->cacheResults($statement);

        $result = [];
        foreach ($cacheData['results'] as $row) {
            $result[] = array_map('strval', array_values($row));
        }
        return $result;
    }

    /**
     * Fetches all rows from the result set as an array of arrays, where each row is both numerically and associatively indexed.
     * All values are converted to strings. If there are no more rows, an empty array is returned.
     *
     * @return array An array of the row values as strings, with both numerical and associative indexes, or an empty array if no more rows, or null on error.
     */
    public function internalFetchAllBoth(): array
    {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return [];
        }
        $cacheData = $this->getStrategy()->cacheResults($statement);

        $result = [];
        foreach ($cacheData['results'] as $row) {
            $combined = [];
            $index = 0;
            foreach ($row as $key => $value) {
                $combined[$index] = (string) $value;
                $combined[$key] = (string) $value;
                $index++;
            }
            $result[] = $combined;
        }
        return $result;
    }

    /**
     * Fetches all values of a single column from the result set as an array of strings.
     *
     * @param int $columnIndex Index of the value to fetch. If not provided, fetches the first column.
     * @return array An array of the column values as strings, or an empty array if no more rows, or null on error.
     */
    public function internalFetchAllColumn(int $columnIndex = 0): array
    {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return [];
        }
        $cacheData = $this->getStrategy()->cacheResults($statement);

        $result = [];
        foreach ($cacheData['results'] as $row) {
            $values = array_values($row);
            if (isset($values[$columnIndex])) {
                $result[] = (string) $values[$columnIndex];
            }
        }
        return $result;
    }

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
    public function internalFetchAllClass(?array $constructorArguments = [], ?string $aClassOrObject = '\stdClass'): array
    {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return [];
        }
        $cacheData = $this->getStrategy()->cacheResults($statement);

        $result = [];
        foreach ($cacheData['results'] as $row) {
            $result[] = Reflections::createObjectAndSetPropertiesCaseInsensitive(
                $aClassOrObject,
                $constructorArguments ?? [],
                $row
            );
        }
        return $result;
    }
}
