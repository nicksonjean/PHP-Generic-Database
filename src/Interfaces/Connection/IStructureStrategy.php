<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces\Connection;

/**
 * Strategy for structure operations used by StatementsHandler and TransactionsHandler.
 * Allows DML (INSERT/UPDATE/DELETE) and transactions without coupling to JSONConnection private methods.
 *
 * @package GenericDatabase\Interfaces\Connection;
 */
interface IStructureStrategy
{
    /**
     * Load data from a JSON table file.
     *
     * @param string|null $table The table name (JSON file without extension).
     * @return array
     */
    public function load(?string $table = null): array;

    /**
     * Get the current data.
     *
     * @return array
     */
    public function getData(): array;

    /**
     * Set the data.
     *
     * @param array $data The data.
     * @return void
     */
    public function setData(array $data): void;

    /**
     * Save data to a JSON table file.
     *
     * @param array $data The data to save.
     * @param string|null $table The table name (optional).
     * @return bool
     */
    public function save(array $data, ?string $table = null): bool;
}
