<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces\Connection;

use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Generic\Connection\Structure;

/**
 * This interface defines the structure for a database in the application.
 * Implementing classes should provide the necessary methods to handle database configurations.
 *
 * @package PHP-Generic-Database\Interfaces\Connection
 * @subpackage IStructure
 */
interface IStructure
{
    /**
     * Gets the structure of the database.
     *
     * @return array|Structure|Exceptions The structure of the database or an exception if getting the structure fails.
     */
    public function mount(): array|Structure|Exceptions;

    /**
     * Get the full file path for a table.
     *
     * @param string $table The table name.
     * @return string The full file path.
     */
    public function getTablePath(string $table): string;

    /**
     * Get the schema.
     *
     * @return Structure|null The schema.
     */
    public function getSchema(): ?Structure;

    /**
     * Get the available tables.
     *
     * @return array The tables list.
     */
    public function getTables(): ?array;

    /**
     * Set the tables list.
     *
     * @param array $tables The tables list.
     * @return void
     */
    public function setTables(array $tables): void;

    /**
     * Get the structure.
     *
     * @return Structure|null The structure.
     */
    public function getStructure(): ?Structure;

    /**
     * Set the structure.
     *
     * @param Structure $structure The structure.
     * @return void
     */
    public function setStructure(array|Structure|Exceptions $structure): void;

    /**
     * Load data from a table/file.
     *
     * @param string|null $table The table name (file without extension).
     * @return array The loaded data.
     */
    public function load(?string $table = null): array;

    /**
     * Get the current data.
     *
     * @return array The current data.
     */
    public function getData(): array;

    /**
     * Set the data.
     *
     * @param array $data The data to set.
     * @return void
     */
    public function setData(array $data): void;

    /**
     * Get the current active table.
     *
     * @return string|null The current table name.
     */
    public function getCurrentTable(): ?string;

    /**
     * Set the current active table.
     *
     * @param string|null $table The table name.
     * @return void
     */
    public function setCurrentTable(?string $table): void;

    /**
     * Save data to a table/file.
     *
     * @param array $data The data to save.
     * @param string|null $table The table name (optional).
     * @return bool True on success, false on failure.
     */
    public function save(array $data, ?string $table = null): bool;

    /**
     * Reset the data state.
     *
     * @return void
     */
    public function reset(): void;
}
