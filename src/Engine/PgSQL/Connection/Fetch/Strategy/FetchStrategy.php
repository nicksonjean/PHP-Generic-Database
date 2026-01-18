<?php

namespace GenericDatabase\Engine\PgSQL\Connection\Fetch\Strategy;

use GenericDatabase\Interfaces\Connection\IFetchStrategy;
use GenericDatabase\Helpers\Types\Specials\Resources;
use GenericDatabase\Generic\Fetch\FetchCache;
use PgSql\Result;

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
            $this->cachedResults[$resourceId] = [];
            if ($resource instanceof Result) {
                while ($row = pg_fetch_array($resource, null, PGSQL_ASSOC)) {
                    $normalizedRow = [];
                    foreach ($row as $key => $value) {
                        if (is_string($key)) {
                            $normalizedRow[$key] = $value;
                        }
                    }
                    $this->cachedResults[$resourceId][] = $normalizedRow;
                }
                pg_result_seek($resource, 0);
            } elseif (is_array($resource)) {
                $this->cachedResults[$resourceId] = $resource['results'] ?? [];
            }
            $this->positions[$resourceId] = 0;
        }
        return $resourceId;
    }
}

