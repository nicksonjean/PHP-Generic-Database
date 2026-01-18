<?php

namespace GenericDatabase\Generic\Statements;

/**
 * This class provides metadata for a database query, including the query string,
 * arguments, columns, and rows.
 *
 * Methods:
 * - `getString(): string`: Retrieves the query string.
 * - `setString(string $string): self`: Sets the query string.
 * - `getArguments(): ?array`: Retrieves the query arguments.
 * - `setArguments(?array $arguments): self`: Sets the query arguments.
 * - `getColumns(): int`: Retrieves the number of columns.
 * - `setColumns(int $columns): self`: Sets the number of columns.
 * - `getRows(): RowsMetadata`: Retrieves the rows metadata.
 *
 * Fields:
 * - `$string`: The query string.
 * - `$arguments`: The query arguments.
 * - `$columns`: The number of columns in the query result.
 * - `$rows`: The metadata for the rows in the query result.
 */
class QueryMetadata
{
    /**
     * Represents the metadata of a database query, including the query string, arguments, column count, and row metadata.
     */
    public string $string = '';

    /**
     * Holds the arguments for the database query.
     */
    public ?array $arguments = [];

    /**
     * The number of columns returned by the database query.
     */
    public int $columns = 0;

    /**
     * Holds the metadata for the rows returned by the database query.
     */
    public RowsMetadata $rows;

    /**
     * QueryMetadata constructor.
     * Initializes a new instance of the RowsMetadata class.
     */
    public function __construct()
    {
        $this->rows = new RowsMetadata();
    }

    /**
     * Gets the query string.
     *
     * @return string The query string.
     */
    public function getString(): string
    {
        return $this->string;
    }

    /**
     * Sets the query string.
     *
     * @param string $string The query string.
     * @return self Returns the current instance for method chaining.
     */
    public function setString(string $string): self
    {
        $this->string = $string;
        return $this;
    }

    /**
     * Gets the query arguments.
     *
     * @return array|null The query arguments, or null if none are set.
     */
    public function getArguments(): ?array
    {
        return $this->arguments;
    }

    /**
     * Sets the query arguments.
     *
     * @param array|null $arguments The query arguments.
     * @return self Returns the current instance for method chaining.
     */
    public function setArguments(?array $arguments): self
    {
        $this->arguments = $arguments;
        return $this;
    }

    /**
     * Gets the number of columns.
     *
     * @return int The number of columns.
     */
    public function getColumns(): int
    {
        return $this->columns;
    }

    /**
     * Sets the number of columns.
     *
     * @param int $columns The number of columns.
     * @return self Returns the current instance for method chaining.
     */
    public function setColumns(int $columns): self
    {
        $this->columns = $columns;
        return $this;
    }

    /**
     * Gets the rows metadata.
     *
     * @return RowsMetadata The row's metadata.
     */
    public function getRows(): RowsMetadata
    {
        return $this->rows;
    }
}

