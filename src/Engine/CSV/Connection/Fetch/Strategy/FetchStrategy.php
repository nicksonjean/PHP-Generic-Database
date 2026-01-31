<?php

namespace GenericDatabase\Engine\CSV\Connection\Fetch\Strategy;

use GenericDatabase\Interfaces\Connection\IFetchStrategy;
use GenericDatabase\Helpers\Types\Specials\Resources;
use GenericDatabase\Generic\Fetch\FetchCache;
use GenericDatabase\Engine\CSV\Connection\CSV;

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
                $this->cachedResults[$resourceId] = $this->parseCsvString($resource);
            } else {
                $this->cachedResults[$resourceId] = [];
            }
            $this->positions[$resourceId] = 0;
        }

        return $resourceId;
    }

    /**
     * Parse a CSV string into an array of associative rows.
     *
     * @param string $content The CSV content string.
     * @return array
     */
    private function parseCsvString(string $content): array
    {
        $delimiter = CSV::getDelimiter();
        $enclosure = CSV::getEnclosure();
        $escape = CSV::getEscape();
        $hasHeader = CSV::hasHeader();

        $lines = preg_split('/\r\n|\r|\n/', $content);
        if ($lines === false || empty($lines)) {
            return [];
        }

        $data = [];
        $headers = null;

        foreach ($lines as $line) {
            if (CSV::getSkipEmptyLines() && trim($line) === '') {
                continue;
            }

            $row = str_getcsv($line, $delimiter, $enclosure, $escape);

            if ($hasHeader && $headers === null) {
                $headers = $row;
                continue;
            }

            if ($headers !== null) {
                $padded = array_slice(array_pad($row, count($headers), null), 0, count($headers));
                $row = array_combine($headers, $padded);
            } else {
                $row = array_combine(array_map(fn($i) => 'col' . ($i + 1), array_keys($row)), $row);
            }

            if ($row !== false) {
                $data[] = $row;
            }
        }

        return $data;
    }
}
