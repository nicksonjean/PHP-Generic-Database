<?php

namespace GenericDatabase\Interfaces\Connection;

/**
 * This interface defines the strategy for fetching data from a database.
 * Implementations of this interface should provide specific methods for
 * retrieving data according to the defined strategy.
 *
 * @package PHP-Generic-Database\Interfaces\Connection
 * @subpackage IFetchStrategy
 */
interface IFetchStrategy
{
    /**
     * Gets a unique identifier for the given resource.
     *
     * @param mixed $resource The resource to get the identifier for It can be of type resource, object, or array.
     * @return string A unique identifier for the resource. If the resource is a resource type, it returns its string representation. If it's an object, it returns the object's hash. If it's an array, it returns the MD5 hash of the serialized array. Returns 'null' if the resource type is not recognized.
     */
    public function getResourceId(mixed $resource): string;

    /**
     * Caches the results from a statement for future use
     *
     * @param mixed $resource The statement to cache results from
     * @return array An associative array containing the cached results, the current position and the statement identifier
     * @psalm-return array{results: array, position: int, id: string}
     */
    public function cacheResults(mixed $resource): array;

    /**
     * Handles resetting the fetch position and caching results if not already cached
     *
     * @param mixed $resource The statement resource
     * @return void
     */
    public function handleFetchReset(mixed $resource): void;
}
