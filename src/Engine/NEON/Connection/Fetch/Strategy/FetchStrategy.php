<?php

namespace GenericDatabase\Engine\NEON\Connection\Fetch\Strategy;

use GenericDatabase\Interfaces\Connection\IFetchStrategy;
use GenericDatabase\Helpers\Types\Specials\Resources;
use GenericDatabase\Helpers\Parsers\NEON as NeonParser;
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
            } elseif (is_string($resource)) {
                $parsed = NeonParser::parseTableNeonString($resource);
                $this->cachedResults[$resourceId] = is_array($parsed) ? $parsed : [];
            } else {
                $this->cachedResults[$resourceId] = [];
            }
            $this->positions[$resourceId] = 0;
        }

        return $resourceId;
    }
}
