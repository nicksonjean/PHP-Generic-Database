<?php

namespace GenericDatabase\Abstract;

use ReflectionException;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IFetchAbstract;
use GenericDatabase\Interfaces\Connection\IFetchStrategy;

/**
 * The `GenericDatabase\Abstract\AbstractFetch` class implements the `IFetchAbstract` interface
 * and serves as a base class for fetching data in a generic database context. It manages the
 * database connection and the fetch strategy, and provides a set of methods for fetching data
 * in various formats (e.g., as objects, associative arrays, numeric arrays, etc.).
 *
 * Main functionalities:
 * - Manages the database connection and fetch strategy.
 * - Provides a base implementation for fetching data from a database using a generic approach.
 * - Supports fetching data in various formats (e.g., as objects, associative arrays, numeric arrays, etc.).
 * - Acts as a foundation for implementing the IFetchAbstract interface.
 *
 * Methods:
 * - `getStrategy(): IFetchStrategy:` Returns the current fetch strategy.
 * - `getInstance(): IConnection:` Returns the database connection instance.
 * - `setStrategy(IFetchStrategy $strategy): void:` Sets the fetch strategy.
 * - `internalFetchAssoc(): array|false|null:` Fetches the current row as an associative array.
 * - `internalFetchNum(): array|false|null:` Fetches the current row as a numeric array.
 * - `internalFetchBoth(): bool|array:` Fetches the current row as both an associative and a numeric array.
 * - `internalFetchColumn(int $columnIndex = 0): false|string:` Fetches a single value from the result set, or false if there are no more rows.
 * - `internalFetchClass(?array $constructorArguments = null, ?string $aClassOrObject = '\stdClass'): object|false:` Fetches a single row from the result set as an object.
 * - `internalFetchAllAssoc(): array:` Fetches all rows from the result set as an associative array, or an empty array if there are no more rows.
 * - `internalFetchAllNum(): array:` Fetches all rows from the result set as a numeric array, or an empty array if there are no more rows.
 * - `internalFetchAllBoth(): array:` Fetches all rows from the result set as an array of arrays, where each row is both numerically and associatively indexed.
 * - `internalFetchAllColumn(int $columnIndex = 0): array:` Fetches all values of a single column from the result set as an array of strings.
 * - `internalFetchAllClass(?array $constructorArguments = [], ?string $aClassOrObject = '\stdClass'): array:` Fetches all rows from the result set as an array of objects, each one being an instance of the provided class.
 *
 * Fields:
 * - `$instance`: The connection instance used for dynamic operations.
 * - `$fetchStrategy`: The options handler for managing configuration.
 *
 * Concrete implementations of this class should inherit from `AbstractFetch` and provide their own implementation of the database connection and fetch strategy.
 *
 * @package PHP-Generic-Database
 * @subpackage Abstract
 * @category Database
 * @abstract
 */
abstract class AbstractFetch implements IFetchAbstract
{
    /** @var IConnection Database connection instance */
    protected static IConnection $instance;

    /** @var IFetchStrategy Strategy for fetching results */
    protected static IFetchStrategy $fetchStrategy;

    /**
     * Initialize fetch abstraction with connection and strategy
     *
     * @param IConnection $instance Database connection instance
     * @param IFetchStrategy $fetchStrategy Strategy for fetching results
     */
    public function __construct(IConnection $instance, IFetchStrategy $fetchStrategy)
    {
        self::$instance = $instance;
        self::$fetchStrategy = $fetchStrategy;
    }

    /**
     * Get the current fetch strategy
     *
     * @return IFetchStrategy Current fetch strategy instance
     */
    public function getStrategy(): IFetchStrategy
    {
        return self::$fetchStrategy;
    }

    /**
     * Get the database connection instance
     *
     * @return IConnection Current database connection instance
     */
    public function getInstance(): IConnection
    {
        return self::$instance;
    }

    /**
     * Fetches a single row from the result set as an object
     *
     * @param array|null $constructorArguments Arguments to pass to class constructor
     * @param string|null $aClassOrObject Class name or object to hydrate
     * @return object|false Object instance or false if no more rows
     * @throws ReflectionException If reflection fails
     */
    public function internalFetchClass(?array $constructorArguments = null, ?string $aClassOrObject = '\stdClass'): object|false
    {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return false;
        }
        $cacheData = $this->getStrategy()->cacheResults($statement);
        $results = $cacheData['results'];
        $position = &$cacheData['position'];
        $exhausted = &$cacheData['exhausted'];

        if (isset($results[$position])) {
            $row = $results[$position++];
            return Reflections::createObjectAndSetPropertiesCaseInsensitive(
                $aClassOrObject,
                $constructorArguments ?? [],
                $row
            );
        }

        $exhausted = true;
        return false;
    }

    /**
     * Fetches the current row as both an associative and a numeric array.
     *
     * @return bool|array Returns an associative and numeric array if successful, false on failure.
     */
    public function internalFetchBoth(): bool|array
    {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return false;
        }
        $cacheData = $this->getStrategy()->cacheResults($statement);
        $results = $cacheData['results'];
        $position = &$cacheData['position'];
        $exhausted = &$cacheData['exhausted'];

        if (isset($results[$position])) {
            $row = $results[$position++];
            $result = [];
            $index = 0;
            foreach ($row as $key => $value) {
                $result[$index] = (string) $value;
                $result[$key] = (string) $value;
                $index++;
            }
            return $result;
        }

        $exhausted = true;
        return false;
    }

    /**
     * Fetches a single row from the result set as a numerically indexed array,
     * converting all values to strings, or returns false if there are no more rows.
     *
     * @return array|false|null A numerically indexed array of the row values as strings, false if no more rows, or null on error.
     */
    public function internalFetchAssoc(): array|null|false
    {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return false;
        }
        $cacheData = $this->getStrategy()->cacheResults($statement);
        $results = $cacheData['results'];
        $position = &$cacheData['position'];
        $exhausted = &$cacheData['exhausted'];

        if (isset($results[$position])) {
            return $results[$position++];
        }

        $exhausted = true;
        return false;
    }

    /**
     * Fetches the current row as a numeric array.
     *
     * @return array|false|null Returns a numeric array if successful, null if no row was found, or false on failure.
     */
    public function internalFetchNum(): array|false|null
    {
        $statement = $this->getInstance()->getStatement();
        if (!$statement) {
            return false;
        }
        $cacheData = $this->getStrategy()->cacheResults($statement);
        $results = $cacheData['results'];
        $position = &$cacheData['position'];
        $exhausted = &$cacheData['exhausted'];

        if (isset($results[$position])) {
            $row = array_values($results[$position++]);
            return array_map('strval', $row);
        }

        $exhausted = true;
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
        $position = &$cacheData['position'];
        $exhausted = &$cacheData['exhausted'];

        if (isset($results[$position])) {
            $row = $results[$position++];
            $values = array_values($row);
            return isset($values[$columnIndex]) ? (string) $values[$columnIndex] : false;
        }

        $exhausted = true;
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

