<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces\Connection;

/**
 * Extended fetch interface for flat-file databases (JSON, CSV).
 * Adds cache management and execution methods specific to flat-file operations.
 *
 * @package GenericDatabase\Interfaces\Connection
 */
interface IFlatFileFetch extends IFetch
{
    /**
     * Clear the cached result set for new queries.
     *
     * @return void
     */
    public function clearCache(): void;

    /**
     * Execute the query to populate metadata.
     * Resets cursor to allow subsequent fetch operations.
     *
     * @return void
     */
    public function execute(): void;
}
