<?php

namespace GenericDatabase\Engine\SQLite\Connection\Fetch\Strategy;

use GenericDatabase\Interfaces\Connection\IFetchStrategy;
use GenericDatabase\Helpers\Types\Specials\Resources;
use GenericDatabase\Generic\Fetch\FetchCache;
use SQLite3Stmt;
use SQLite3Result;

class FetchStrategy implements IFetchStrategy
{
    use Resources;
    use FetchCache;

    /**
     * Caches the results from a statement for future use
     *
     * @param mixed $resource The statement to cache results from
     * @return string The statement identifier
     */
    public function cacheResource(mixed $resource): string
    {
        $resourceId = $this->getResourceId($resource);
        if (!isset($this->cachedResults[$resourceId])) {
            if ($resource instanceof SQLite3Stmt) {
                $result = $resource->execute();
                if ($result instanceof SQLite3Result) {
                    $this->cachedResults[$resourceId] = [];
                    while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                        $this->cachedResults[$resourceId][] = $row;
                    }
                    $result->reset();
                }
            } elseif ($resource instanceof SQLite3Result) {
                $this->cachedResults[$resourceId] = [];
                while ($row = $resource->fetchArray(SQLITE3_ASSOC)) {
                    $this->cachedResults[$resourceId][] = $row;
                }
                $resource->reset();
            } elseif (is_array($resource)) {
                $this->cachedResults[$resourceId] = $resource['results'] ?? [];
            }
            $this->positions[$resourceId] = 0;
        }

        return $resourceId;
    }
}

