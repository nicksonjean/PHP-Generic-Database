<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces\Connection;

/**
 * Extended statements interface for flat-file databases (JSON, CSV).
 * Adds fetched rows tracking and last insert ID management.
 *
 * @package GenericDatabase\Interfaces\Connection
 */
interface IFlatFileStatements extends IStatements
{
    /**
     * Get the number of fetched rows.
     *
     * @return int
     */
    public function getFetchedRows(): int;

    /**
     * Set the number of fetched rows.
     *
     * @param int $params The number of fetched rows.
     * @return void
     */
    public function setFetchedRows(int $params): void;

    /**
     * Set the last insert ID.
     *
     * @param int $id The last insert ID.
     * @return void
     */
    public function setLastInsertId(int $id): void;
}
