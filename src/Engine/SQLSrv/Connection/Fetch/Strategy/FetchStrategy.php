<?php

namespace GenericDatabase\Engine\SQLSrv\Connection\Fetch\Strategy;

use GenericDatabase\Interfaces\Connection\IFetchStrategy;
use GenericDatabase\Helpers\Types\Specials\Resources;
use GenericDatabase\Generic\Fetch\FetchCache;

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
            if (is_resource($resource)) {
                $currentRow = sqlsrv_fetch_array($resource, SQLSRV_FETCH_ASSOC);
                if ($currentRow !== false && $currentRow !== null) {
                    do {
                        $normalizedRow = [];
                        $numericIndex = 0;
                        foreach ($currentRow as $key => $value) {
                            if (is_string($key)) {
                                $normalizedRow[$key] = $value;
                                $normalizedRow[$numericIndex] = $value;
                                $numericIndex++;
                            }
                        }
                        $this->cachedResults[$resourceId][] = $normalizedRow;
                    } while ($currentRow = sqlsrv_fetch_array($resource, SQLSRV_FETCH_ASSOC));
                }
                sqlsrv_fetch($resource, SQLSRV_SCROLL_FIRST);
            } elseif (is_array($resource)) {
                $this->cachedResults[$resourceId] = $resource['results'] ?? [];
            }
            $this->positions[$resourceId] = 0;
        }
        return $resourceId;
    }
}

