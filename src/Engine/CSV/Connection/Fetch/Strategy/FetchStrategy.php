<?php

namespace GenericDatabase\Engine\CSV\Connection\Fetch\Strategy;

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
            if (is_array($resource)) {
                $this->cachedResults[$resourceId] = $resource;
            } elseif (is_string($resource) && is_array(json_decode($resource, true))) {
                $this->cachedResults[$resourceId] = json_decode($resource, true);
            } else {
                $this->cachedResults[$resourceId] = [];
            }
            $this->positions[$resourceId] = 0;
        }

        return $resourceId;
    }
}
