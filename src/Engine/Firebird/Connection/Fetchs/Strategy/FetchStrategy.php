<?php

namespace GenericDatabase\Engine\Firebird\Connection\Fetchs\Strategy;

use GenericDatabase\Interfaces\Fetchs\Strategy\IFetchStrategy;
use GenericDatabase\Helpers\Types\Specials\Resources;
use GenericDatabase\Generic\Fetchs\FetchCache;

class FetchStrategy implements IFetchStrategy
{
    use Resources, FetchCache;

    /**
     * Caches the results from a statement for future use
     *
     * @param mixed $resource The statement to cache results from
     * @return string The statement identifier
     */
    protected function cacheResource(mixed $resource): string
    {
        if (!$resource) {
            return 'null';
        }
        $resourceId = $this->getResourceId($resource);
        if (!isset($this->cachedResults[$resourceId])) {
            $this->cachedResults[$resourceId] = [];
            if (is_resource($resource)) {
                while ($row = ibase_fetch_assoc($resource)) {
                    $normalizedRow = [];
                    foreach ($row as $key => $value) {
                        if (is_string($key)) {
                            $normalizedRow[$key] = $value;
                        }
                    }
                    $this->cachedResults[$resourceId][] = $normalizedRow;
                }
                ibase_free_result($resource);
            } elseif (is_array($resource)) {
                $this->cachedResults[$resourceId] = $resource['results'] ?? [];
            }
            $this->positions[$resourceId] = 0;
        }
        return $resourceId;
    }
}
