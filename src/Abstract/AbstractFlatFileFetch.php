<?php

declare(strict_types=1);

namespace GenericDatabase\Abstract;

use ReflectionException;
use GenericDatabase\Helpers\Reflections;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IFetchStrategy;
use GenericDatabase\Interfaces\Connection\IFlatFileFetch;
use GenericDatabase\Interfaces\Connection\IStructure;

/**
 * Abstract base class for flat-file fetch operations.
 * Provides common SQL parsing and cursor management for JSON/CSV engines.
 * Extends AbstractFetch but overrides methods to work with local result sets
 * instead of relying on getStatement().
 *
 * @package GenericDatabase\Abstract
 */
abstract class AbstractFlatFileFetch extends AbstractFetch implements IFlatFileFetch
{
    /**
     * @var int Current cursor position.
     */
    protected int $cursor = 0;

    /**
     * @var array|null Cached result set.
     */
    protected ?array $resultSet = null;

    /**
     * @var IStructure|null Structure handler.
     */
    protected ?IStructure $structureHandler = null;

    /**
     * Constructor - passes through to parent.
     *
     * @param IConnection $instance Database connection instance.
     * @param IFetchStrategy $fetchStrategy Strategy for fetching results.
     * @param IStructure|null $structureHandler Structure handler.
     */
    public function __construct(
        IConnection $instance,
        IFetchStrategy $fetchStrategy,
        ?IStructure $structureHandler = null
    ) {
        parent::__construct($instance, $fetchStrategy);
        $this->structureHandler = $structureHandler;
    }

    /**
     * Get the structure handler.
     *
     * @return IStructure|null
     */
    protected function getStructureHandler(): ?IStructure
    {
        return $this->structureHandler;
    }

    /**
     * Clear the cached result set for new queries.
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->resultSet = null;
        $this->cursor = 0;
    }

    /**
     * Execute the stored query and populate metadata.
     * Resets cursor for subsequent fetch operations.
     *
     * @return void
     */
    public function execute(): void
    {
        if ($this->resultSet === null) {
            $this->resultSet = $this->executeStoredQuery();
        }
        $this->cursor = 0;
    }

    /**
     * Execute the stored query and return results.
     * Must be implemented by concrete classes.
     *
     * @return array The result set.
     */
    abstract protected function executeStoredQuery(): array;

    /**
     * Get the current result set, executing query if needed.
     *
     * @return array
     */
    protected function getResultSet(): array
    {
        if ($this->resultSet === null) {
            $this->resultSet = $this->executeStoredQuery();
        }
        return $this->resultSet;
    }

    /**
     * Format row for FETCH_BOTH mode (numeric + associative keys).
     *
     * @param array $row The row data.
     * @return array
     */
    protected function formatBothMode(array $row): array
    {
        $result = [];
        $index = 0;
        foreach ($row as $key => $value) {
            $result[$index] = $value;
            $result[$key] = $value;
            $index++;
        }
        return $result;
    }

    /**
     * Fetch row into a class instance.
     *
     * @param array $row The row data.
     * @param string|null $className The class name.
     * @param mixed $ctorArgs Constructor arguments.
     * @return object The class instance.
     * @throws ReflectionException
     */
    protected function fetchClass(array $row, ?string $className, mixed $ctorArgs): object
    {
        if ($className === null) {
            return (object) $row;
        }

        return Reflections::createObjectAndSetPropertiesCaseInsensitive(
            $className,
            $ctorArgs ?? [],
            $row
        );
    }

    /**
     * Fetch row into an existing object.
     *
     * @param array $row The row data.
     * @param object|null $object The object to populate.
     * @return object The populated object.
     */
    protected function fetchInto(array $row, ?object $object): object
    {
        if ($object === null) {
            return (object) $row;
        }

        foreach ($row as $key => $value) {
            $object->$key = $value;
        }

        return $object;
    }

    /**
     * Internal fetch for associative array - overrides parent to use local result set.
     *
     * @return array|false|null
     */
    public function internalFetchAssoc(): array|null|false
    {
        $results = $this->getResultSet();

        if ($this->cursor >= count($results)) {
            return false;
        }

        return $results[$this->cursor++];
    }

    /**
     * Internal fetch for numeric array - overrides parent to use local result set.
     *
     * @return array|false|null
     */
    public function internalFetchNum(): array|false|null
    {
        $results = $this->getResultSet();

        if ($this->cursor >= count($results)) {
            return false;
        }

        $row = array_values($results[$this->cursor++]);
        return array_map('strval', $row);
    }

    /**
     * Internal fetch for both mode - overrides parent to use local result set.
     *
     * @return bool|array
     */
    public function internalFetchBoth(): bool|array
    {
        $results = $this->getResultSet();

        if ($this->cursor >= count($results)) {
            return false;
        }

        $row = $results[$this->cursor++];
        $result = [];
        $index = 0;
        foreach ($row as $key => $value) {
            $result[$index] = (string) $value;
            $result[$key] = (string) $value;
            $index++;
        }
        return $result;
    }

    /**
     * Internal fetch for column - overrides parent to use local result set.
     *
     * @param int $columnIndex
     * @return mixed
     */
    public function internalFetchColumn(int $columnIndex = 0): mixed
    {
        $results = $this->getResultSet();

        if ($this->cursor >= count($results)) {
            return false;
        }

        $row = $results[$this->cursor++];
        $values = array_values($row);
        return isset($values[$columnIndex]) ? (string) $values[$columnIndex] : false;
    }

    /**
     * Internal fetch for class - overrides parent to use local result set.
     *
     * @param array|null $constructorArguments
     * @param string|null $aClassOrObject
     * @return object|false
     * @throws ReflectionException
     */
    public function internalFetchClass(?array $constructorArguments = null, ?string $aClassOrObject = '\stdClass'): object|false
    {
        $results = $this->getResultSet();

        if ($this->cursor >= count($results)) {
            return false;
        }

        $row = $results[$this->cursor++];
        return Reflections::createObjectAndSetPropertiesCaseInsensitive(
            $aClassOrObject,
            $constructorArguments ?? [],
            $row
        );
    }

    /**
     * Internal fetch all for associative array - overrides parent.
     *
     * @return array
     */
    public function internalFetchAllAssoc(): array
    {
        return $this->getResultSet();
    }

    /**
     * Internal fetch all for numeric array - overrides parent.
     *
     * @return array
     */
    public function internalFetchAllNum(): array
    {
        $results = $this->getResultSet();
        $result = [];
        foreach ($results as $row) {
            $result[] = array_map('strval', array_values($row));
        }
        return $result;
    }

    /**
     * Internal fetch all for both mode - overrides parent.
     *
     * @return array
     */
    public function internalFetchAllBoth(): array
    {
        $results = $this->getResultSet();
        $result = [];
        foreach ($results as $row) {
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
     * Internal fetch all for column - overrides parent.
     *
     * @param int $columnIndex
     * @return array
     */
    public function internalFetchAllColumn(int $columnIndex = 0): array
    {
        $results = $this->getResultSet();
        $result = [];
        foreach ($results as $row) {
            $values = array_values($row);
            if (isset($values[$columnIndex])) {
                $result[] = (string) $values[$columnIndex];
            }
        }
        return $result;
    }

    /**
     * Internal fetch all for class - overrides parent.
     *
     * @param array|null $constructorArguments
     * @param string|null $aClassOrObject
     * @return array
     * @throws ReflectionException
     */
    public function internalFetchAllClass(?array $constructorArguments = [], ?string $aClassOrObject = '\stdClass'): array
    {
        $results = $this->getResultSet();
        $result = [];
        foreach ($results as $row) {
            $result[] = Reflections::createObjectAndSetPropertiesCaseInsensitive(
                $aClassOrObject,
                $constructorArguments ?? [],
                $row
            );
        }
        return $result;
    }
}
