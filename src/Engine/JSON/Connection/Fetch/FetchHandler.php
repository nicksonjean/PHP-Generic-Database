<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\JSON\Connection\Fetch;

use GenericDatabase\Interfaces\Connection\IFetch;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Engine\JSON\Connection\JSON;
use GenericDatabase\Engine\JSON\QueryBuilder\Regex;
use GenericDatabase\Generic\FlatFiles\DataProcessor;

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
    private function getConnection(): IConnection
    {
        return $this->connection;
    }

    /**
     * Set the result set for fetching.
     *
     * @param array $resultSet The result set.
     * @return void
     */
    private function setResultSet(array $resultSet): void
    {
        $this->resultSet = $resultSet;
        $this->cursor = 0;
    }

    /**
     * Reset the cursor to the beginning.
     *
     * @return void
     */
    private function reset(): void
    {
        $this->cursor = 0;
    }

    /**
     * Clear the cached result set (for new queries).
     *
     * @return void
     */
    public function clearCache(): void
    {
        $this->resultSet = null;
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

        try {
            // Replace parameters in the query string
            $processedQuery = $this->replaceQueryParameters($queryString, $queryParameters);

            // Parse and execute the query
            $result = $this->parseAndExecuteQuery($processedQuery);

            // Update metadata with actual counts
            $rowCount = count($result);
            $columnCount = !empty($result) ? count((array) reset($result)) : 0;

            if (method_exists($this->connection, 'setQueryRows')) {
                $this->connection->setQueryRows($rowCount);
            }
            if (method_exists($this->connection, 'setQueryColumns')) {
                $this->connection->setQueryColumns($columnCount);
            }

            // Set fetched rows (the actual number of rows returned by the query)
            if (method_exists($this->connection, 'setFetchedRows')) {
                $this->connection->setFetchedRows($rowCount);
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

        // Check if parameters is a multidimensional array (batch operation)
        // In this case, we can't do simple replacement - return query as-is
        $firstValue = reset($parameters);
        if (is_array($firstValue) && !empty($firstValue)) {
            return $query;
        }

        foreach ($parameters as $key => $value) {
            $placeholder = is_int($key) ? '?' : $key;

            // Skip array values (shouldn't happen for SELECT, but safety check)
            if (is_array($value)) {
                continue;
            }

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
     * Parse and execute a SQL query using the DataProcessor directly.
     *
     * @param string $query The SQL query.
     * @return array The result set.
     */
    private function parseAndExecuteQuery(string $query): array
    {
        // Parse SQL query to extract components
        $query = trim($query);

        // Extract table name from FROM clause and load data
        $tableName = null;
        if (preg_match('/\bFROM\s+(\w+)/i', $query, $matches)) {
            $tableName = $matches[1];
            if (method_exists($this->connection, 'load')) {
                $this->connection->load($tableName);
            }
        }

        // Get data from connection
        $data = method_exists($this->connection, 'getData') ? $this->connection->getData() : [];

        // Create DataProcessor instance
        $processor = new DataProcessor($data);

        // Extract and apply WHERE clause
        if (preg_match('/\bWHERE\s+(.*?)(?:\s+ORDER\s+BY|\s+GROUP\s+BY|\s+LIMIT|$)/is', $query, $matches)) {
            $whereClause = trim($matches[1]);
            $conditions = $this->parseWhereClause($whereClause);
            if (!empty($conditions)) {
                $processor->where($conditions, 'AND');
            }
        }

        // Extract and apply ORDER BY clause
        if (preg_match('/\bORDER\s+BY\s+(.*?)(?:\s+LIMIT|$)/is', $query, $matches)) {
            $orderClause = trim($matches[1]);
            // Check for ASC/DESC
            if (preg_match('/^(.*?)\s+(ASC|DESC)$/i', $orderClause, $orderMatches)) {
                $orderColumn = trim($orderMatches[1]);
                $direction = strtoupper($orderMatches[2]) === 'DESC' ? DataProcessor::DESC : DataProcessor::ASC;
                $processor->orderBy($orderColumn, $direction);
            } else {
                $processor->orderBy($orderClause, DataProcessor::ASC);
            }
        }

        // Extract and apply LIMIT clause
        if (preg_match('/\bLIMIT\s+(\d+)(?:\s*,\s*(\d+))?/i', $query, $matches)) {
            if (isset($matches[2])) {
                $processor->limit((int) $matches[2], (int) $matches[1]);
            } else {
                $processor->limit((int) $matches[1]);
            }
        }

        // Get filtered data
        $result = $processor->getData();

        // Extract SELECT columns and apply aliases
        if (preg_match('/^SELECT\s+(.*?)\s+FROM/is', $query, $matches)) {
            $columns = trim($matches[1]);
            if ($columns !== '*') {
                $columnParts = array_map('trim', explode(',', $columns));
                $result = $this->applySelectColumns($result, $columnParts);
            }
        }

        return $result;
    }

    /**
     * Parse WHERE clause and return conditions array for DataProcessor.
     *
     * @param string $whereClause The WHERE clause string.
     * @return array The conditions array.
     */
    private function parseWhereClause(string $whereClause): array
    {
        $conditions = [];

        // Split by AND, preserving BETWEEN...AND
        $parts = $this->splitByTopLevelAndOperator($whereClause);

        foreach ($parts as $part) {
            $part = trim($part);
            if (empty($part)) {
                continue;
            }

            // Use Regex::parseWhereCondition for parsing
            $parsed = Regex::parseWhereCondition($part);

            if ($parsed !== null) {
                $condition = [
                    'column' => $parsed['column'],
                    'operator' => $parsed['operator'],
                ];

                // Handle different operators
                switch (strtoupper($parsed['operator'])) {
                    case 'BETWEEN':
                    case 'NOT BETWEEN':
                        $condition['value'] = [
                            'min' => $parsed['value'],
                            'max' => $parsed['value2'] ?? $parsed['value']
                        ];
                        break;

                    case 'IN':
                    case 'NOT IN':
                        // Parse IN values - can be comma-separated
                        $values = array_map(function ($v) {
                            return trim(trim($v), "'\"");
                        }, explode(',', $parsed['value']));
                        $condition['value'] = $values;
                        break;

                    case 'LIKE':
                    case 'NOT LIKE':
                        // Keep the LIKE pattern as is (with % wildcards)
                        $condition['value'] = $parsed['value'];
                        break;

                    default:
                        $condition['value'] = $parsed['value'];
                        break;
                }

                $conditions[] = $condition;
            }
        }

        return $conditions;
    }

    /**
     * Apply SELECT columns with aliases to result set.
     *
     * @param array $data The data to transform.
     * @param array $columns The column specifications.
     * @return array The transformed data.
     */
    private function applySelectColumns(array $data, array $columns): array
    {
        return array_map(function ($row) use ($columns) {
            $result = [];
            foreach ($columns as $col) {
                $col = trim($col);

                // Check if column has an alias (column AS alias)
                if (preg_match('/^(.+?)\s+AS\s+(.+)$/i', $col, $matches)) {
                    $originalColumn = trim($matches[1], '"\'`');
                    $alias = trim($matches[2], '"\'`');

                    if (isset($row[$originalColumn])) {
                        $result[$alias] = $row[$originalColumn];
                    }
                } else {
                    // No alias, use column name as is
                    $cleanCol = trim($col, '"\'`');
                    if (isset($row[$cleanCol])) {
                        $result[$cleanCol] = $row[$cleanCol];
                    }
                }
            }
            return $result;
        }, $data);
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
    private function formatRow(mixed $row, int $fetchStyle, mixed $fetchArgument = null, mixed $optArgs = null): mixed
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

        $fetchStyle = $fetchStyle ?? JSON::FETCH_ASSOC;
        $result = [];

        foreach ($this->resultSet as $row) {
            $result[] = $this->formatRow($row, $fetchStyle, $fetchArgument, $optArgs);
        }

        $this->cursor = count($this->resultSet);
        return $result;
    }

    /**
     * Execute the query without returning results (for metadata population).
     * Resets cursor to allow subsequent fetch operations.
     *
     * @return void
     */
    public function execute(): void
    {
        if ($this->resultSet === null) {
            $this->resultSet = $this->executeStoredQuery();
        }
        // Reset cursor for subsequent fetch operations
        $this->cursor = 0;
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

    /**
     * Split WHERE clause by AND operator, but preserve BETWEEN...AND constructs.
     *
     * @param string $clause The clause to split.
     * @return array The split conditions.
     */
    private function splitByTopLevelAndOperator(string $clause): array
    {
        $parts = [];
        $current = '';
        $i = 0;
        $len = strlen($clause);
        $inBetween = false;

        while ($i < $len) {
            // Check for BETWEEN keyword (start of BETWEEN...AND construct)
            if (!$inBetween && preg_match('/\bBETWEEN\b/i', substr($clause, $i, 7))) {
                $inBetween = true;
                $current .= substr($clause, $i, 7);
                $i += 7;
                continue;
            }

            // Check for AND keyword
            if (strtoupper(substr($clause, $i, 3)) === 'AND') {
                // Check if this is a word boundary
                $beforeOk = ($i === 0) || !ctype_alnum($clause[$i - 1]);
                $afterOk = ($i + 3 >= $len) || !ctype_alnum($clause[$i + 3]);

                if ($beforeOk && $afterOk) {
                    if ($inBetween) {
                        // This AND is part of BETWEEN...AND, include it
                        $current .= 'AND';
                        $i += 3;
                        $inBetween = false; // BETWEEN...AND complete
                        continue;
                    } else {
                        // This is a logical AND separator
                        if (!empty(trim($current))) {
                            $parts[] = trim($current);
                        }
                        $current = '';
                        $i += 3;
                        // Skip whitespace after AND
                        while ($i < $len && ctype_space($clause[$i])) {
                            $i++;
                        }
                        continue;
                    }
                }
            }

            $current .= $clause[$i];
            $i++;
        }

        if (!empty(trim($current))) {
            $parts[] = trim($current);
        }

        return !empty($parts) ? $parts : [$clause];
    }
}
