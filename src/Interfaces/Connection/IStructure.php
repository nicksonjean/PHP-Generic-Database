<?php

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
    public function getTables(): array;

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
    public function setStructure(Structure $structure): void;
}
