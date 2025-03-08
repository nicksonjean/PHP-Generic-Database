<?php

namespace GenericDatabase\Shared;

use ReflectionClass;
use ReflectionException;
use GenericDatabase\Helpers\Exceptions;

/**
 * This trait provides a mechanism to manage a collection of items within a class.
 * Ensures the collection is initialized and accessible, allowing items
 * to be added, retrieved, checked for existence, and removed.
 *
 * Methods:
 * - `ensureCollectionExists(): array:` Ensures the collection property exists and is initialized.
 * - `initializeCollection(): void:` Initializes the collection property if it does not exist.
 * - `add(mixed $object, string|null $name): void:` Adds an item to the collection.
 * - `get(string $name): mixed:` Retrieves an item from the collection by name.
 * - `contains(string $name): bool:` Checks if an item exists in the collection.
 * - `remove(string $name): void:` Removes an item from the collection by name.
 *
 */
trait Registry
{
    /**
     * Ensures the collection property exists in the consuming class.
     * Initializes it if it does not exist.
     *
     * @return array Reference to the collection property.
     * @throws ReflectionException
     * @noinspection PhpExpressionResultUnusedInspection
     */
    private function &ensureCollectionExists(): array
    {
        $reflection = new ReflectionClass($this);

        if (!$reflection->hasProperty('collection')) {
            $this->initializeCollection();
        }

        $property = $reflection->getProperty('collection');
        $property->setAccessible(true); //NOSONAR

        $collection = $property->getValue($this); //NOSONAR
        if ($collection === null) {
            $collection = [];
            $property->setValue($this, $collection); //NOSONAR
        }

        $return = $property->getValue($this); //NOSONAR
        return $return;
    }

    /**
     * Dynamically initializes the $collection property in the class.
     * @throws ReflectionException
     * @noinspection PhpExpressionResultUnusedInspection
     */
    private function initializeCollection(): void
    {
        $reflection = new ReflectionClass($this);

        if (!$reflection->hasProperty('collection')) {
            $reflection->getProperty('collection') ?? $reflection->newInstanceWithoutConstructor(); //NOSONAR
            $property = $reflection->getProperty('collection');
            $property->setAccessible(true); //NOSONAR
            $property->setValue($this, []); //NOSONAR
        }
    }

    /**
     * Adds an item to the registry.
     * @param mixed $object
     * @param string|null $name
     * @throws Exceptions|ReflectionException
     */
    public function add(mixed $object, string $name = null): void
    {
        if (empty($name)) {
            throw new Exceptions('You must pass in a name to store an item in the registry.');
        }

        $collection = &$this->ensureCollectionExists();
        $collection[$name] = $object;
    }

    /**
     * Gets an item from the registry.
     * @param string $name
     * @return mixed|null
     * @throws ReflectionException
     */
    public function get(string $name): mixed
    {
        $collection = $this->ensureCollectionExists();
        return $collection[$name] ?? null;
    }

    /**
     * Checks if an item exists in the registry.
     * @param string $name
     * @return bool
     * @throws ReflectionException
     */
    public function contains(string $name): bool
    {
        $collection = $this->ensureCollectionExists();
        return isset($collection[$name]);
    }

    /**
     * Removes an item from the registry.
     * @param string $name
     * @throws ReflectionException
     */
    public function remove(string $name): void
    {
        $collection = &$this->ensureCollectionExists();
        if (isset($collection[$name])) {
            unset($collection[$name]);
        }
    }
}
