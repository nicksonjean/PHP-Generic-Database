<?php

namespace GenericDatabase\Generic\Fetch;

/**
 * Provides caching functionality for fetch operations, allowing results to be stored
 * and retrieved efficiently. It maintains the state of cached results, their positions,
 * and handles resetting fetch positions when necessary.
 *
 * Methods:
 * - `cacheResults(mixed $resource): array:` Caches the results from a statement for future use.
 * - `handleFetchReset(mixed $resource): void:` Handles resetting the fetch position and caching results if not already cached.
 * - `cacheResource(mixed $resource): array:` Abstract method to be implemented in the class using this trait.
 *
 * Fields:
 * - `$cachedResults`: Stores the cached results of fetch operations.
 * - `$positions`: Keeps track of the positions of fetched data.
 * - `$lastFetchAll`: Stores the results of the last fetch all operation.
 *
 * @package GenericDatabase\Generic
 * @subpackage Fetch
 */
trait FetchCache
{
    /**
     * @var array $cachedResults Stores the cached results of fetch operations.
     */
    private array $cachedResults = [];

    /**
     * @var array $positions Keeps track of the positions of fetched data.
     */
    private array $positions = [];

    /**
     * @var array $lastFetchAll Stores the results of the last fetch all operation.
     */
    private array $lastFetchAll = [];

    /**
     * @var array $exhausted Tracks if cursor was exhausted (returned false).
     */
    private array $exhausted = [];

    /**
     * Caches the results from a statement for future use
     *
     * @param mixed $resource The statement to cache results from
     * @return array An associative array containing the cached results, the current position and the statement identifier
     * @psalm-return array{results: array, position: int, id: string}
     */
    public function cacheResults(mixed $resource): array
    {
        $resourceId = $this->cacheResource($resource);
        $results = $this->cachedResults[$resourceId];
        $resultCount = count($results);

        if (!isset($this->exhausted[$resourceId])) {
            $this->exhausted[$resourceId] = false;
        }

        if ($this->exhausted[$resourceId] && $resultCount > 0) {
            $this->positions[$resourceId] = 0;
            $this->exhausted[$resourceId] = false;
        }

        return [
            'results' => $results,
            'position' => &$this->positions[$resourceId],
            'id' => $resourceId,
            'exhausted' => &$this->exhausted[$resourceId]
        ];
    }

    /**
     * Handles resetting the fetch position and caching results if not already cached
     *
     * @param mixed $resource The statement resource
     * @return void
     */
    public function handleFetchReset(mixed $resource): void
    {
        $resourceId = $this->cacheResource($resource);
        if (isset($this->lastFetchAll[$resourceId])) {
            $this->positions[$resourceId] = 0;
            unset($this->lastFetchAll[$resourceId]);
        }
    }

    /**
     * Método abstrato para ser implementado na classe que usa a trait
     *
     * @param mixed $resource
     * @return string
     */
    abstract protected function cacheResource(mixed $resource): string;
}
