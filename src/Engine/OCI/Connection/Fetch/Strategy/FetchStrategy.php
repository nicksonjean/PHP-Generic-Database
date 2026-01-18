<?php

namespace GenericDatabase\Engine\OCI\Connection\Fetch\Strategy;

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
                while ($row = oci_fetch_array($resource, OCI_ASSOC + OCI_RETURN_NULLS)) {
                    $normalizedRow = [];
                    foreach ($row as $key => $value) {
                        if (is_string($key)) {
                            $normalizedRow[$key] = $value;
                            $normalizedRow[array_search($key, array_keys($row))] = $value;
                        }
                    }
                    $this->cachedResults[$resourceId][] = $normalizedRow;
                }
                oci_execute($resource);
            } elseif (is_array($resource)) {
                $this->cachedResults[$resourceId] = $resource['results'] ?? [];
            }
            $this->positions[$resourceId] = 0;
        }
        return $resourceId;
    }
}

