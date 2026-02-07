<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces\Connection;

/**
 * Optional interface for connections that maintain a fetch cache.
 * When implemented, the StatementsHandler may call clearFetchCache() before
 * prepare/query to avoid stale results. Only PDO and ODBC implement this interface.
 *
 * @package PHP-Generic-Database\Interfaces\Connection
 * @subpackage IFetchCache
 */
interface IFetchCache
{
    /**
     * Clears the fetch cache to avoid returning stale results from previous queries.
     * Called by the engine's StatementsHandler before each new prepare/query.
     *
     * @return void
     */
    public function clearFetchCache(): void;
}
