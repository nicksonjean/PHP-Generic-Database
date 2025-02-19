<?php

namespace GenericDatabase\Engine\PDO\Connection\Fetchs\Strategy;

use GenericDatabase\Interfaces\Fetchs\Strategy\IFetchStrategy;
use GenericDatabase\Helpers\Types\Specials\Resources;
use GenericDatabase\Generic\Fetchs\FetchCache;
use PDO;
use PDOStatement;

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
            if ($resource instanceof PDOStatement) {
                $result = $resource->execute();
                if ($result) {
                    $this->cachedResults[$resourceId] = $resource->fetchAll(PDO::FETCH_ASSOC);
                    $resource->closeCursor();
                }
            } else {
                $this->cachedResults[$resourceId] = $resource['results'] ?? [];
            }
            $this->positions[$resourceId] = 0;
        }
        return $resourceId;
    }
}
