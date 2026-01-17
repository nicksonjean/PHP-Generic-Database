<?php

namespace GenericDatabase\Engine\ODBC\Connection\Fetch\Strategy;

use GenericDatabase\Interfaces\Connection\IFetchStrategy;
use GenericDatabase\Helpers\Types\Specials\Resources;
use GenericDatabase\Generic\Fetch\FetchCache;
use GenericDatabase\Engine\ODBC\Connection\ODBC;

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
            if (is_resource($resource) || (PHP_VERSION_ID >= 80400 && is_object($resource) && get_class($resource) === 'Odbc\Result')) {
                while ($row = odbc_fetch_array($resource)) {
                    $normalizedRow = [];
                    $numericIndex = 0;
                    foreach ($row as $key => $value) {
                        if (is_string($key)) {
                            $normalizedRow[$key] = $value;
                            $normalizedRow[$numericIndex] = $value;
                            $numericIndex++;
                        }
                    }
                    $this->cachedResults[$resourceId][] = $normalizedRow;
                }
                if (function_exists('odbc_free_result')) {
                    odbc_free_result($resource);
                }
            } elseif (is_array($resource)) {
                $this->cachedResults[$resourceId] = $resource['results'] ?? [];
            }
            $this->positions[$resourceId] = 0;
        }
        return $resourceId;
    }
}
