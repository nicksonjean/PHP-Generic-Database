<?php

namespace GenericDatabase\Engine\MySQLi\Connection\Fetch\Strategy;

use GenericDatabase\Interfaces\Connection\IFetchStrategy;
use GenericDatabase\Helpers\Types\Specials\Resources;
use GenericDatabase\Generic\Fetch\FetchCache;
use mysqli_result;
use mysqli_stmt;

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
            if ($resource instanceof mysqli_stmt) {
                $result = $resource->get_result();
                if ($result) {
                    $this->cachedResults[$resourceId] = [];
                    while ($row = $result->fetch_assoc()) {
                        $this->cachedResults[$resourceId][] = $row;
                    }
                    $result->data_seek(0);
                }
            } elseif ($resource instanceof mysqli_result) {
                $this->cachedResults[$resourceId] = [];
                while ($row = $resource->fetch_assoc()) {
                    $this->cachedResults[$resourceId][] = $row;
                }
                $resource->data_seek(0);
            } elseif (is_array($resource)) {
                $this->cachedResults[$resourceId] = $resource['results'] ?? [];
            }
            $this->positions[$resourceId] = 0;
        }
        return $resourceId;
    }
}

