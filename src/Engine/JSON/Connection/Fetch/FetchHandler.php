<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\JSON\Connection\Fetch;

use GenericDatabase\Interfaces\Connection\IFetch;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Engine\JSON\Connection\JSON;

/**
 * Handles fetch operations for JSON connections.
 *
 * @package GenericDatabase\Engine\JSON\Connection\Fetch
 */
class FetchHandler implements IFetch
{
    /**
     * @var IConnection The connection instance.
     */
    private IConnection $connection;

    /**
     * @var int Current cursor position.
     */
    private int $cursor = 0;

    /**
     * @var array|null Cached result set.
     */
    private ?array $resultSet = null;

    /**
     * @var mixed The fetch strategy.
     */
    private mixed $strategy;

    /**
     * Constructor.
     *
     * @param IConnection $connection The connection instance.
     * @param mixed|null $strategy The fetch strategy (optional).
     */
    public function __construct(IConnection $connection, mixed $strategy = null)
    {
        $this->connection = $connection;
        $this->strategy = $strategy;
    }

    /**
     * Get the connection instance.
     *
     * @return IConnection
     */
    public function getConnection(): IConnection
    {
        return $this->connection;
    }

    /**
     * Set the result set for fetching.
     *
     * @param array $resultSet The result set.
     * @return void
     */
    public function setResultSet(array $resultSet): void
    {
        $this->resultSet = $resultSet;
        $this->cursor = 0;
    }

    /**
     * Reset the cursor to the beginning.
     *
     * @return void
     */
    public function reset(): void
    {
        $this->cursor = 0;
    }

    /**
     * Execute a stored query and return the result set.
     *
     * @return array The result set.
     */
    private function executeStoredQuery(): array
    {
        // Get the stored query string and parameters
        $queryString = method_exists($this->connection, 'getQueryString')
            ? $this->connection->getQueryString()
            : '';

        $queryParameters = method_exists($this->connection, 'getQueryParameters')
            ? $this->connection->getQueryParameters()
            : null;

        // If no query is stored, return raw data
        if (empty($queryString)) {
            if (method_exists($this->connection, 'getData')) {
                return $this->connection->getData();
            }
            return [];
        }

        // Use JSONQueryBuilder to process the SQL query
        $queryBuilderClass = '\\GenericDatabase\\Engine\\JSONQueryBuilder';
        if (!class_exists($queryBuilderClass)) {
            // Fallback to raw data if QueryBuilder not available
            if (method_exists($this->connection, 'getData')) {
                return $this->connection->getData();
            }
            return [];
        }

        try {
            // Replace parameters in the query string
            $processedQuery = $this->replaceQueryParameters($queryString, $queryParameters);

            // Create a query builder instance and execute the query
            $builder = $queryBuilderClass::with($this->connection);

            // Parse and execute the query using the builder
            $result = $this->parseAndExecuteQuery($builder, $processedQuery);

            // Update metadata with actual counts
            $rowCount = count($result);
            $columnCount = !empty($result) ? count((array) reset($result)) : 0;

            if (method_exists($this->connection, 'setQueryRows')) {
                $this->connection->setQueryRows($rowCount);
            }
            if (method_exists($this->connection, 'setQueryColumns')) {
                $this->connection->setQueryColumns($columnCount);
            }

            // Reset affected rows for SELECT queries
            if (method_exists($this->connection, 'setAffectedRows')) {
                $this->connection->setAffectedRows(0);
            }

            return $result;
        } catch (\Exception $e) {
            // On error, return empty array
            return [];
        }
    }

    /**
     * Replace query parameters with their values.
     *
     * @param string $query The query string.
     * @param array|null $parameters The parameters.
     * @return string The processed query.
     */
    private function replaceQueryParameters(string $query, ?array $parameters): string
    {
        if (empty($parameters)) {
            return $query;
        }

        foreach ($parameters as $key => $value) {
            $placeholder = is_int($key) ? '?' : $key;

            // Format value based on type
            if (is_null($value)) {
                $formattedValue = 'NULL';
            } elseif (is_bool($value)) {
                $formattedValue = $value ? '1' : '0';
            } elseif (is_numeric($value)) {
                $formattedValue = (string) $value;
            } else {
                $formattedValue = "'" . addslashes((string) $value) . "'";
            }

            // Replace placeholder
            if (is_int($key)) {
                // Replace first occurrence of ? with the value
                $pos = strpos($query, '?');
                if ($pos !== false) {
                    $query = substr_replace($query, $formattedValue, $pos, 1);
                }
            } else {
                // Replace named parameter
                $query = str_replace($placeholder, $formattedValue, $query);
            }
        }

        return $query;
    }

    /**
     * Parse and execute a SQL query using the query builder.
     *
     * @param object $builder The query builder instance.
     * @param string $query The SQL query.
     * @return array The result set.
     */
    private function parseAndExecuteQuery(object $builder, string $query): array
    {
        // Parse SQL query to extract components
        $query = trim($query);

        // Extract table name from FROM clause
        if (preg_match('/\bFROM\s+(\w+)/i', $query, $matches)) {
            $tableName = $matches[1];
            $builder->from($tableName);
        }

        // Extract SELECT columns with aliases
        if (preg_match('/^SELECT\s+(.*?)\s+FROM/is', $query, $matches)) {
            $columns = trim($matches[1]);
            if ($columns !== '*') {
                // Parse column list and handle aliases
                $columnParts = array_map('trim', explode(',', $columns));
                $selectColumns = [];

                foreach ($columnParts as $column) {
                    // Check if column has an alias (column AS alias)
                    if (preg_match('/^(.+?)\s+AS\s+(.+)$/i', $column, $aliasMatch)) {
                        // Format: "originalColumn AS aliasName"
                        $selectColumns[] = trim($aliasMatch[1]) . ' AS ' . trim($aliasMatch[2]);
                    } else {
                        $selectColumns[] = $column;
                    }
                }

                $builder->select(...$selectColumns);
            }
        }

        // Extract WHERE clause
        if (preg_match('/\bWHERE\s+(.*?)(?:\s+ORDER\s+BY|\s+GROUP\s+BY|\s+LIMIT|$)/is', $query, $matches)) {
            $whereClause = trim($matches[1]);
            $builder->where($whereClause);
        }

        // Extract ORDER BY clause
        if (preg_match('/\bORDER\s+BY\s+(.*?)(?:\s+LIMIT|$)/is', $query, $matches)) {
            $orderClause = trim($matches[1]);
            // Check for ASC/DESC
            if (preg_match('/^(.*?)\s+(ASC|DESC)$/i', $orderClause, $orderMatches)) {
                $orderColumn = trim($orderMatches[1]);
                $direction = strtoupper($orderMatches[2]);
                if ($direction === 'DESC') {
                    $builder->orderDesc($orderColumn);
                } else {
                    $builder->orderAsc($orderColumn);
                }
            } else {
                $builder->order($orderClause);
            }
        }

        // Extract LIMIT clause
        if (preg_match('/\bLIMIT\s+(\d+)(?:\s*,\s*(\d+))?/i', $query, $matches)) {
            if (isset($matches[2])) {
                $builder->limit($matches[1], $matches[2]);
            } else {
                $builder->limit($matches[1]);
            }
        }

        // Execute and return results
        return $builder->fetchAll(JSON::FETCH_ASSOC);
    }

    /**
     * Fetch the next row from the result set.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return mixed The fetched row or false if no more rows.
     */
    public function fetch(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        if ($this->resultSet === null) {
            $this->resultSet = $this->executeStoredQuery();
        }

        if ($this->cursor >= count($this->resultSet)) {
            return false;
        }

        $row = $this->resultSet[$this->cursor++];
        return $this->formatRow($row, $fetchStyle ?? JSON::FETCH_ASSOC, $fetchArgument, $optArgs);
    }

    /**
     * Fetch all rows from the result set.
     *
     * @param int|null $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return array|bool The fetched rows or false on failure.
     */
    public function fetchAll(?int $fetchStyle = null, mixed $fetchArgument = null, mixed $optArgs = null): array|bool
    {
        if ($this->resultSet === null) {
            $this->resultSet = $this->executeStoredQuery();
        }

        $result = [];
        $style = $fetchStyle ?? JSON::FETCH_ASSOC;

        foreach ($this->resultSet as $row) {
            $result[] = $this->formatRow($row, $style, $fetchArgument, $optArgs);
        }

        $this->cursor = count($this->resultSet);
        return $result;
    }

    /**
     * Format a row based on the fetch style.
     *
     * @param mixed $row The row to format.
     * @param int $fetchStyle The fetch style.
     * @param mixed|null $fetchArgument The fetch argument.
     * @param mixed|null $optArgs Additional options.
     * @return mixed The formatted row.
     */
    public function formatRow(mixed $row, int $fetchStyle, mixed $fetchArgument = null, mixed $optArgs = null): mixed
    {
        $row = (array) $row;

        return match ($fetchStyle) {
            JSON::FETCH_NUM => array_values($row),
            JSON::FETCH_BOTH => $this->formatBothMode($row),
            JSON::FETCH_OBJ => (object) $row,
            JSON::FETCH_COLUMN => $fetchArgument !== null
                ? ($row[$fetchArgument] ?? array_values($row)[0] ?? null)
                : (array_values($row)[0] ?? null),
            JSON::FETCH_CLASS => $this->fetchClass($row, $fetchArgument, $optArgs),
            JSON::FETCH_INTO => $this->fetchInto($row, $fetchArgument),
            default => $row, // FETCH_ASSOC
        };
    }

    /**
     * Format row for FETCH_BOTH mode with alternating indices.
     *
     * @param array $row The row data.
     * @return array The formatted row with alternating numeric and associative indices.
     */
    private function formatBothMode(array $row): array
    {
        $result = [];
        $index = 0;

        foreach ($row as $key => $value) {
            $result[$index] = $value;      // Numeric index
            $result[$key] = $value;         // Associative index
            $index++;
        }

        return $result;
    }

    /**
     * Fetch row into a class instance.
     *
     * @param array $row The row data.
     * @param string|null $className The class name.
     * @param mixed $ctorArgs Constructor arguments.
     * @return object The class instance.
     */
    private function fetchClass(array $row, ?string $className, mixed $ctorArgs): object
    {
        if ($className === null) {
            return (object) $row;
        }

        $instance = $ctorArgs !== null
            ? new $className(...(array) $ctorArgs)
            : new $className();

        foreach ($row as $key => $value) {
            $instance->$key = $value;
        }

        return $instance;
    }

    /**
     * Fetch row into an existing object.
     *
     * @param array $row The row data.
     * @param object|null $object The object to populate.
     * @return object The populated object.
     */
    private function fetchInto(array $row, ?object $object): object
    {
        if ($object === null) {
            return (object) $row;
        }

        foreach ($row as $key => $value) {
            $object->$key = $value;
        }

        return $object;
    }
}

