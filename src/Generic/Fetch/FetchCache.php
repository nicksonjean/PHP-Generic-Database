<?php

namespace GenericDatabase\Generic\Fetch;

trait FetchCache
{
    private array $cachedResults = [];
    private array $positions = [];
    private array $lastFetchAll = [];

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
        return [
            'results' => $this->cachedResults[$resourceId],
            'position' => &$this->positions[$resourceId],
            'id' => $resourceId
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
     * @param mixed $resource
     * @return string
     */
    abstract protected function cacheResource(mixed $resource): string;
}
