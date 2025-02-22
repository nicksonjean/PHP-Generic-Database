<?php

namespace GenericDatabase\Engine\ODBC\Connection\Fetch\Strategy;

use GenericDatabase\Interfaces\Fetch\Strategy\IFetchStrategy;
use GenericDatabase\Helpers\Types\Specials\Resources;
use GenericDatabase\Generic\Fetch\FetchCache;

class FetchStrategy implements IFetchStrategy
{
    use Resources, FetchCache;

    /**
     * Caches the results from a statement for future use
     *
     * @param mixed $resource The statement to cache results from
     * @return string The statement identifier
     */
    private function cacheResource(mixed $resource): string
    {
        $resourceId = $this->getResourceId($resource);
        if (!isset($this->cachedResults[$resourceId])) {
            if (is_resource($resource)) {
                $results = [];
                while ($row = odbc_fetch_array($resource)) {
                    $results[] = $row;
                }
                $this->cachedResults[$resourceId] = $results;
                odbc_free_result($resource);
            } else {
                $this->cachedResults[$resourceId] = $resource['results'] ?? [];
            }
            $this->positions[$resourceId] = 0;
        }
        return $resourceId;
    }
}
