<?php

declare(strict_types=1);

namespace GenericDatabase\Interfaces;

/**
 * Provides an interface for connecting to flat file databases (JSON, CSV, XML, YAML).
 *
 * @package PHP-Generic-Database\Interfaces
 * @subpackage IFlatFileConnection
 */
interface IFlatFileConnection
{
    /**
     * Gets the available tables (files) in the database directory.
     *
     * @return array The list of table names (files without extension).
     */
    public function getTables(): array;

    /**
     * Sets the tables list manually.
     *
     * @param array $tables The list of table names.
     * @return void
     */
    public function setTables(array $tables): void;

    /**
     * Gets the schema definition if available.
     *
     * @return array|null The schema definition or null if not set.
     */
    public function getSchema(): ?array;

    /**
     * Sets the schema definition for the flat file.
     *
     * @param array|null $schema The schema definition.
     * @return void
     */
    public function setSchema(?array $schema): void;

    /**
     * Loads data from the flat file.
     *
     * @param string|null $table The table name to load (optional).
     * @return array The loaded data.
     */
    public function load(?string $table = null): array;

    /**
     * Saves data to the flat file.
     *
     * @param array $data The data to save.
     * @param string|null $table The table name to save to (optional).
     * @return bool True on success, false on failure.
     */
    public function save(array $data, ?string $table = null): bool;

    /**
     * Gets the current data from the connection.
     *
     * @return array The current data.
     */
    public function getData(): array;

    /**
     * Sets the data for the connection.
     *
     * @param array $data The data to set.
     * @return void
     */
    public function setData(array $data): void;

    /**
     * Inserts a new row into the flat file.
     *
     * @param array $row The row to insert.
     * @return bool True on success, false on failure.
     */
    public function insert(array $row): bool;

    /**
     * Updates rows matching the criteria.
     *
     * @param array $data The data to update.
     * @param array $where The criteria for matching rows.
     * @return int The number of affected rows.
     */
    public function update(array $data, array $where): int;

    /**
     * Deletes rows matching the criteria.
     *
     * @param array $where The criteria for matching rows.
     * @return int The number of deleted rows.
     */
    public function delete(array $where): int;

    /**
     * Selects rows matching the criteria.
     *
     * @param array $columns The columns to select.
     * @param array $where The criteria for matching rows.
     * @return array The selected rows.
     */
    public function selectWhere(array $columns, array $where): array;
}
