<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\JSON\Connection\Statements;

use GenericDatabase\Interfaces\Connection\IStatements;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Generic\FlatFiles\DataProcessor;
use GenericDatabase\Helpers\Types\Compounds\Arrays;

/**
 * Handles SQL-like statement operations for JSON connections.
 *
 * @package GenericDatabase\Engine\JSON\Connection\Statements
 */
class StatementsHandler implements IStatements
{
    /**
     * @var IConnection The connection instance.
     */
    private IConnection $connection;

    /**
     * @var string The current query string.
     */
    private string $queryString = '';

    /**
     * @var array|null The query parameters.
     */
    private ?array $queryParameters = null;

    /**
     * @var int|false The number of query rows.
     */
    private int|false $queryRows = 0;

    /**
     * @var int|false The number of query columns.
     */
    private int|false $queryColumns = 0;

    /**
     * @var int|false The number of affected rows.
     */
    private int|false $affectedRows = 0;

    /**
     * @var int The number of fetched rows.
     */
    private int $fetchedRows = 0;

    /**
     * @var mixed The current statement/result.
     */
    private mixed $statement = null;

    /**
     * @var int The last insert ID.
     */
    private int $lastInsertId = 0;

    /**
     * @var mixed The options handler.
     */
    private mixed $optionsHandler;

    /**
     * @var mixed The report handler.
     */
    private mixed $reportHandler;

    /**
     * Constructor.
     *
     * @param IConnection $connection The connection instance.
     * @param mixed|null $optionsHandler The options handler (optional).
     * @param mixed|null $reportHandler The report handler (optional).
     */
    public function __construct(IConnection $connection, mixed $optionsHandler = null, mixed $reportHandler = null)
    {
        $this->connection = $connection;
        $this->optionsHandler = $optionsHandler;
        $this->reportHandler = $reportHandler;
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
     * Get the connection instance (alias for getConnection).
     *
     * @return IConnection
     */
    public function getInstance(): IConnection
    {
        return $this->connection;
    }

    /**
     * Reset all metadata.
     *
     * @return void
     */
    public function setAllMetadata(): void
    {
        $this->queryRows = 0;
        $this->queryColumns = 0;
        $this->affectedRows = 0;
        $this->fetchedRows = 0;
    }

    /**
     * Get all metadata.
     *
     * @return object
     */
    public function getAllMetadata(): object
    {
        $metadata = new \GenericDatabase\Generic\Statements\Metadata();
        $metadata->query->setString($this->queryString);
        $metadata->query->setArguments($this->queryParameters);
        $metadata->query->setColumns($this->queryColumns);
        $metadata->query->rows->setFetched($this->fetchedRows > 0 ? $this->fetchedRows : $this->queryRows);
        $metadata->query->rows->setAffected($this->affectedRows);
        return $metadata;
    }

    /**
     * Get the query string.
     *
     * @return string
     */
    public function getQueryString(): string
    {
        return $this->queryString;
    }

    /**
     * Set the query string.
     *
     * @param string $params The query string.
     * @return void
     */
    public function setQueryString(string $params): void
    {
        $this->queryString = $params;
    }

    /**
     * Get the query parameters.
     *
     * @return array|null
     */
    public function getQueryParameters(): ?array
    {
        return $this->queryParameters;
    }

    /**
     * Set the query parameters.
     *
     * @param array|null $params The parameters.
     * @return void
     */
    public function setQueryParameters(?array $params): void
    {
        $this->queryParameters = $params;
    }

    /**
     * Get the number of query rows.
     *
     * @return int|false
     */
    public function getQueryRows(): int|false
    {
        return $this->queryRows;
    }

    /**
     * Set the number of query rows.
     *
     * @param callable|int|false $params The number of rows.
     * @return void
     */
    public function setQueryRows(callable|int|false $params): void
    {
        $this->queryRows = is_callable($params) ? $params() : $params;
    }

    /**
     * Get the number of query columns.
     *
     * @return int|false
     */
    public function getQueryColumns(): int|false
    {
        return $this->queryColumns;
    }

    /**
     * Set the number of query columns.
     *
     * @param int|false $params The number of columns.
     * @return void
     */
    public function setQueryColumns(int|false $params): void
    {
        $this->queryColumns = $params;
    }

    /**
     * Get the number of affected rows.
     *
     * @return int|false
     */
    public function getAffectedRows(): int|false
    {
        return $this->affectedRows;
    }

    /**
     * Set the number of affected rows.
     *
     * @param int|false $params The number of affected rows.
     * @return void
     */
    public function setAffectedRows(int|false $params): void
    {
        $this->affectedRows = $params;
    }

    /**
     * Get the number of fetched rows.
     *
     * @return int
     */
    public function getFetchedRows(): int
    {
        return $this->fetchedRows;
    }

    /**
     * Set the number of fetched rows.
     *
     * @param int $params The number of fetched rows.
     * @return void
     */
    public function setFetchedRows(int $params): void
    {
        $this->fetchedRows = $params;
    }
    /**
     * Get the statement.
     *
     * @return mixed
     */
    public function getStatement(): mixed
    {
        return $this->statement;
    }

    /**
     * Set the statement.
     *
     * @param mixed $statement The statement.
     * @return void
     */
    public function setStatement(mixed $statement): void
    {
        $this->statement = $statement;
    }

    /**
     * Bind a parameter.
     *
     * @param object $params The parameter object.
     * @return void
     */
    public function bindParam(object $params): void
    {
        // Parameters are handled through queryParameters
    }

    /**
     * Parse a query.
     *
     * @param mixed ...$params The parameters.
     * @return string
     */
    public function parse(mixed ...$params): string
    {
        return $params[0] ?? '';
    }

    /**
     * Get the last insert ID.
     *
     * @param string|null $name The name.
     * @return string|int|false
     */
    public function lastInsertId(?string $name = null): string|int|false
    {
        return $this->lastInsertId;
    }

    /**
     * Set the last insert ID.
     *
     * @param int $id The ID.
     * @return void
     */
    public function setLastInsertId(int $insertId): void
    {
        $this->lastInsertId = $insertId;
    }

    /**
     * Quote a value.
     *
     * @param mixed ...$params The value to quote.
     * @return string|int
     */
    public function quote(mixed ...$params): string|int
    {
        $value = $params[0] ?? '';

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_null($value)) {
            return 'NULL';
        }

        return "'" . addslashes((string) $value) . "'";
    }

    /**
     * Execute a query.
     *
     * @param mixed ...$params The query parameters.
     * @return IConnection|null
     * @throws Exceptions
     */
    public function query(mixed ...$params): ?IConnection
    {
        $query = $params[0] ?? '';
        $this->setQueryString($query);
        $this->setAllMetadata();

        // Store the query
        $this->statement = $query;

        // Detect query type and execute DML operations for raw queries
        $queryType = $this->detectQueryType($query);

        if (in_array($queryType, ['INSERT', 'UPDATE', 'DELETE'])) {
            $this->executeRawDmlQuery($query);
        }

        return $this->connection;
    }

    /**
     * Execute a raw DML query (without parameters).
     *
     * @param string $query The SQL query.
     * @return void
     */
    private function executeRawDmlQuery(string $query): void
    {
        $queryType = $this->detectQueryType($query);

        $affected = match ($queryType) {
            'INSERT' => $this->executeRawInsert($query),
            'UPDATE' => $this->executeRawUpdate($query),
            'DELETE' => $this->executeRawDelete($query),
            default => 0
        };

        $this->setAffectedRows($affected);
    }

    /**
     * Execute a raw INSERT query (without parameters).
     *
     * @param string $query The SQL query.
     * @return int The number of affected rows.
     */
    private function executeRawInsert(string $query): int
    {
        // Parse INSERT INTO table (columns) VALUES ('value1', 'value2')
        if (!preg_match('/INSERT\s+INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/i', $query, $matches)) {
            return 0;
        }

        $tableName = $matches[1];
        $columns = array_map('trim', explode(',', $matches[2]));
        $rawValues = $matches[3];

        // Parse values (handle quoted strings)
        $values = $this->parseRawValues($rawValues);

        // Load table data
        if (method_exists($this->connection, 'load')) {
            $this->connection->load($tableName);
        }

        $data = method_exists($this->connection, 'getData') ? $this->connection->getData() : [];

        // Build the row to insert
        $row = [];
        foreach ($columns as $index => $column) {
            $row[$column] = $values[$index] ?? null;
        }

        // Generate auto-increment ID if 'id' column exists and not provided
        if (!isset($row['id']) || $row['id'] === null) {
            $maxId = 0;
            foreach ($data as $existingRow) {
                $existingRow = (array) $existingRow;
                if (isset($existingRow['id']) && (int) $existingRow['id'] > $maxId) {
                    $maxId = (int) $existingRow['id'];
                }
            }
            $row['id'] = $maxId + 1;
        }

        $processor = new DataProcessor($data);
        $result = $processor->insert($row);

        if ($result) {
            $newData = $processor->getData();
            if (method_exists($this->connection, 'setData')) {
                $this->connection->setData($newData);
            }
            if (method_exists($this->connection, 'save')) {
                $this->connection->save($newData, $tableName);
            }
            $this->setLastInsertId((int) $row['id']);
            return 1;
        }

        return 0;
    }

    /**
     * Execute a raw UPDATE query (without parameters).
     *
     * @param string $query The SQL query.
     * @return int The number of affected rows.
     */
    private function executeRawUpdate(string $query): int
    {
        // Parse UPDATE table SET column = 'value' WHERE condition
        if (!preg_match('/UPDATE\s+(\w+)\s+SET\s+(.+?)\s+WHERE\s+(.+)/is', $query, $matches)) {
            return 0;
        }

        $tableName = $matches[1];
        $setClause = $matches[2];
        $whereClause = $matches[3];

        // Load table data
        if (method_exists($this->connection, 'load')) {
            $this->connection->load($tableName);
        }

        $data = method_exists($this->connection, 'getData') ? $this->connection->getData() : [];

        // Parse SET clause (raw values)
        $updateData = $this->parseRawSetClause($setClause);

        // Parse WHERE clause (raw values)
        $conditions = $this->parseRawWhereClause($whereClause);

        $processor = new DataProcessor($data);
        $affected = $processor->update($updateData, $conditions, 'AND');

        if ($affected > 0) {
            $newData = $processor->getData();
            if (method_exists($this->connection, 'setData')) {
                $this->connection->setData($newData);
            }
            if (method_exists($this->connection, 'save')) {
                $this->connection->save($newData, $tableName);
            }
        }

        return $affected;
    }

    /**
     * Execute a raw DELETE query (without parameters).
     *
     * @param string $query The SQL query.
     * @return int The number of deleted rows.
     */
    private function executeRawDelete(string $query): int
    {
        // Parse DELETE FROM table WHERE condition
        if (!preg_match('/DELETE\s+FROM\s+(\w+)\s+WHERE\s+(.+)/is', $query, $matches)) {
            return 0;
        }

        $tableName = $matches[1];
        $whereClause = $matches[2];

        // Load table data
        if (method_exists($this->connection, 'load')) {
            $this->connection->load($tableName);
        }

        $data = method_exists($this->connection, 'getData') ? $this->connection->getData() : [];

        // Parse WHERE clause (raw values)
        $conditions = $this->parseRawWhereClause($whereClause);

        $processor = new DataProcessor($data);
        $deleted = $processor->delete($conditions, 'AND');

        if ($deleted > 0) {
            $newData = $processor->getData();
            if (method_exists($this->connection, 'setData')) {
                $this->connection->setData($newData);
            }
            if (method_exists($this->connection, 'save')) {
                $this->connection->save($newData, $tableName);
            }
        }

        return $deleted;
    }

    /**
     * Parse raw values from VALUES clause.
     *
     * @param string $rawValues The raw values string.
     * @return array The parsed values.
     */
    private function parseRawValues(string $rawValues): array
    {
        $values = [];
        $current = '';
        $inQuote = false;
        $quoteChar = '';

        for ($i = 0; $i < strlen($rawValues); $i++) {
            $char = $rawValues[$i];

            if (!$inQuote && ($char === "'" || $char === '"')) {
                $inQuote = true;
                $quoteChar = $char;
            } elseif ($inQuote && $char === $quoteChar) {
                $inQuote = false;
                $quoteChar = '';
            } elseif (!$inQuote && $char === ',') {
                $values[] = trim(trim($current), "'\"");
                $current = '';
                continue;
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $values[] = trim(trim($current), "'\"");
        }

        return $values;
    }

    /**
     * Parse raw SET clause.
     *
     * @param string $setClause The SET clause string.
     * @return array The parsed update data.
     */
    private function parseRawSetClause(string $setClause): array
    {
        $updateData = [];

        // Split by comma but respect quoted values
        $assignments = $this->splitByCommaRespectingQuotes($setClause);

        foreach ($assignments as $assignment) {
            if (preg_match('/(\w+)\s*=\s*(.+)/', trim($assignment), $matches)) {
                $column = $matches[1];
                $value = trim($matches[2], "'\" \t");
                $updateData[$column] = $value;
            }
        }

        return $updateData;
    }

    /**
     * Parse raw WHERE clause.
     *
     * @param string $whereClause The WHERE clause string.
     * @return array The parsed conditions.
     */
    private function parseRawWhereClause(string $whereClause): array
    {
        $conditions = [];

        // Handle IN clause: WHERE column IN (value1, value2)
        if (preg_match('/(\w+)\s+IN\s*\(([^)]+)\)/i', $whereClause, $matches)) {
            $column = $matches[1];
            $rawValues = $matches[2];
            $values = array_map(function ($val) {
                return trim(trim($val), "'\"");
            }, explode(',', $rawValues));

            $conditions[] = [
                'column' => $column,
                'operator' => 'IN',
                'value' => $values
            ];
            return $conditions;
        }

        // Handle simple conditions
        $parts = preg_split('/\s+AND\s+/i', $whereClause);

        foreach ($parts as $part) {
            $part = trim($part);

            if (preg_match('/(\w+)\s*(=|!=|<>|>|<|>=|<=)\s*(.+)/', $part, $matches)) {
                $column = $matches[1];
                $operator = $matches[2];
                $value = trim($matches[3], "'\" \t");

                $conditions[] = [
                    'column' => $column,
                    'operator' => $operator,
                    'value' => $value
                ];
            }
        }

        return $conditions;
    }

    /**
     * Split string by comma while respecting quoted values.
     *
     * @param string $string The string to split.
     * @return array The split parts.
     */
    private function splitByCommaRespectingQuotes(string $string): array
    {
        $parts = [];
        $current = '';
        $inQuote = false;
        $quoteChar = '';

        for ($i = 0; $i < strlen($string); $i++) {
            $char = $string[$i];

            if (!$inQuote && ($char === "'" || $char === '"')) {
                $inQuote = true;
                $quoteChar = $char;
                $current .= $char;
            } elseif ($inQuote && $char === $quoteChar) {
                $inQuote = false;
                $quoteChar = '';
                $current .= $char;
            } elseif (!$inQuote && $char === ',') {
                $parts[] = trim($current);
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if ($current !== '') {
            $parts[] = trim($current);
        }

        return $parts;
    }

    /**
     * Prepare a statement.
     *
     * @param mixed ...$params The parameters.
     * @return IConnection|null
     */
    public function prepare(mixed ...$params): ?IConnection
    {
        $query = $params[0] ?? '';
        $this->setQueryString($query);
        $this->setAllMetadata();
        $this->statement = $query;

        // Process parameters based on input format
        $parameters = [];
        $isMulti = false;

        if (count($params) > 1) {
            // Check if second parameter is an array (named parameters)
            if (is_array($params[1])) {
                // Check if it's a multidimensional array (batch operation)
                $isMulti = Arrays::isMultidimensional($params[1]);
                $parameters = $params[1];
            } else {
                // Extract positional parameters from remaining arguments
                // Build a mapping from named placeholders to values
                if (preg_match_all('/:(\w+)/', $query, $matches)) {
                    $placeholderNames = $matches[1];

                    // If we have exactly one value and one placeholder, use it directly
                    if (count($params) === 2 && count($placeholderNames) === 1) {
                        $parameters[':' . $placeholderNames[0]] = $params[1];
                    } else {
                        // Map positional arguments to named placeholders
                        for ($i = 1; $i < count($params); $i++) {
                            if (isset($placeholderNames[$i - 1])) {
                                $parameters[':' . $placeholderNames[$i - 1]] = $params[$i];
                            }
                        }
                    }
                }
            }
        }

        $this->setQueryParameters($parameters);

        // Detect query type and execute DML operations
        $queryType = $this->detectQueryType($query);

        if (in_array($queryType, ['INSERT', 'UPDATE', 'DELETE'])) {
            $this->executeDmlQuery($query, $parameters, $isMulti);
        }

        return $this->connection;
    }

    /**
     * Detect the type of SQL query.
     *
     * @param string $query The SQL query.
     * @return string The query type (SELECT, INSERT, UPDATE, DELETE).
     */
    private function detectQueryType(string $query): string
    {
        $query = trim($query);
        $firstWord = strtoupper(strtok($query, " \t\n"));

        return match ($firstWord) {
            'SELECT' => 'SELECT',
            'INSERT' => 'INSERT',
            'UPDATE' => 'UPDATE',
            'DELETE' => 'DELETE',
            default => 'UNKNOWN'
        };
    }

    /**
     * Execute a DML (INSERT, UPDATE, DELETE) query.
     *
     * @param string $query The SQL query.
     * @param array $parameters The query parameters.
     * @param bool $isMulti Whether this is a batch operation.
     * @return void
     */
    private function executeDmlQuery(string $query, array $parameters, bool $isMulti): void
    {
        if ($isMulti) {
            // Batch operation - execute for each parameter set
            $totalAffected = 0;
            foreach ($parameters as $paramSet) {
                $affected = $this->executeSingleDmlQuery($query, $paramSet);
                $totalAffected += $affected;
            }
            $this->setAffectedRows($totalAffected);
        } else {
            $affected = $this->executeSingleDmlQuery($query, $parameters);
            $this->setAffectedRows($affected);
        }
    }

    /**
     * Execute a single DML query with parameters.
     *
     * @param string $query The SQL query.
     * @param array $parameters The query parameters.
     * @return int The number of affected rows.
     */
    private function executeSingleDmlQuery(string $query, array $parameters): int
    {
        $queryType = $this->detectQueryType($query);

        return match ($queryType) {
            'INSERT' => $this->executeInsert($query, $parameters),
            'UPDATE' => $this->executeUpdate($query, $parameters),
            'DELETE' => $this->executeDelete($query, $parameters),
            default => 0
        };
    }

    /**
     * Execute an INSERT query.
     *
     * @param string $query The SQL query.
     * @param array $parameters The query parameters.
     * @return int The number of affected rows (1 on success, 0 on failure).
     */
    private function executeInsert(string $query, array $parameters): int
    {
        // Parse INSERT INTO table (columns) VALUES (...)
        if (!preg_match('/INSERT\s+INTO\s+(\w+)\s*\(([^)]+)\)\s*VALUES\s*\(([^)]+)\)/i', $query, $matches)) {
            return 0;
        }

        $tableName = $matches[1];
        $columns = array_map('trim', explode(',', $matches[2]));
        $valuePlaceholders = array_map('trim', explode(',', $matches[3]));

        // Load table data
        if (method_exists($this->connection, 'load')) {
            $this->connection->load($tableName);
        }

        $data = method_exists($this->connection, 'getData') ? $this->connection->getData() : [];

        // Build the row to insert
        $row = [];
        foreach ($columns as $index => $column) {
            $placeholder = $valuePlaceholders[$index] ?? null;
            if ($placeholder && preg_match('/^:(\w+)$/', $placeholder, $matches)) {
                $paramKey = ':' . $matches[1];
                $row[$column] = $parameters[$paramKey] ?? null;
            }
        }

        // Generate auto-increment ID if 'id' column exists and not provided
        if (!isset($row['id']) || $row['id'] === null) {
            $maxId = 0;
            foreach ($data as $existingRow) {
                $existingRow = (array) $existingRow;
                if (isset($existingRow['id']) && (int) $existingRow['id'] > $maxId) {
                    $maxId = (int) $existingRow['id'];
                }
            }
            $row['id'] = $maxId + 1;
        }

        $processor = new DataProcessor($data);
        $result = $processor->insert($row);

        if ($result) {
            $newData = $processor->getData();
            if (method_exists($this->connection, 'setData')) {
                $this->connection->setData($newData);
            }
            if (method_exists($this->connection, 'save')) {
                $this->connection->save($newData, $tableName);
            }
            $this->setLastInsertId((int) $row['id']);
            return 1;
        }

        return 0;
    }

    /**
     * Execute an UPDATE query.
     *
     * @param string $query The SQL query.
     * @param array $parameters The query parameters.
     * @return int The number of affected rows.
     */
    private function executeUpdate(string $query, array $parameters): int
    {
        // Parse UPDATE table SET column = :value WHERE condition
        if (!preg_match('/UPDATE\s+(\w+)\s+SET\s+(.+?)\s+WHERE\s+(.+)/is', $query, $matches)) {
            return 0;
        }

        $tableName = $matches[1];
        $setClause = $matches[2];
        $whereClause = $matches[3];

        // Load table data
        if (method_exists($this->connection, 'load')) {
            $this->connection->load($tableName);
        }

        $data = method_exists($this->connection, 'getData') ? $this->connection->getData() : [];

        // Parse SET clause
        $updateData = $this->parseSetClause($setClause, $parameters);

        // Parse WHERE clause
        $conditions = $this->parseWhereClauseSimple($whereClause, $parameters);

        $processor = new DataProcessor($data);
        $affected = $processor->update($updateData, $conditions, 'AND');

        if ($affected > 0) {
            $newData = $processor->getData();
            if (method_exists($this->connection, 'setData')) {
                $this->connection->setData($newData);
            }
            if (method_exists($this->connection, 'save')) {
                $this->connection->save($newData, $tableName);
            }
        }

        return $affected;
    }

    /**
     * Execute a DELETE query.
     *
     * @param string $query The SQL query.
     * @param array $parameters The query parameters.
     * @return int The number of deleted rows.
     */
    private function executeDelete(string $query, array $parameters): int
    {
        // Parse DELETE FROM table WHERE condition
        if (!preg_match('/DELETE\s+FROM\s+(\w+)\s+WHERE\s+(.+)/is', $query, $matches)) {
            return 0;
        }

        $tableName = $matches[1];
        $whereClause = $matches[2];

        // Load table data
        if (method_exists($this->connection, 'load')) {
            $this->connection->load($tableName);
        }

        $data = method_exists($this->connection, 'getData') ? $this->connection->getData() : [];

        // Parse WHERE clause
        $conditions = $this->parseWhereClauseSimple($whereClause, $parameters);

        $processor = new DataProcessor($data);
        $deleted = $processor->delete($conditions, 'AND');

        if ($deleted > 0) {
            $newData = $processor->getData();
            if (method_exists($this->connection, 'setData')) {
                $this->connection->setData($newData);
            }
            if (method_exists($this->connection, 'save')) {
                $this->connection->save($newData, $tableName);
            }
        }

        return $deleted;
    }

    /**
     * Parse SET clause for UPDATE queries.
     *
     * @param string $setClause The SET clause string.
     * @param array $parameters The query parameters.
     * @return array The parsed update data.
     */
    private function parseSetClause(string $setClause, array $parameters): array
    {
        $updateData = [];
        $assignments = preg_split('/\s*,\s*/', $setClause);

        foreach ($assignments as $assignment) {
            if (preg_match('/(\w+)\s*=\s*(:?\w+)/', $assignment, $matches)) {
                $column = $matches[1];
                $value = $matches[2];

                if (strpos($value, ':') === 0) {
                    // Named parameter
                    $updateData[$column] = $parameters[$value] ?? null;
                } else {
                    // Literal value
                    $updateData[$column] = trim($value, "'\"");
                }
            }
        }

        return $updateData;
    }

    /**
     * Parse WHERE clause for simple conditions.
     *
     * @param string $whereClause The WHERE clause string.
     * @param array $parameters The query parameters.
     * @return array The parsed conditions.
     */
    private function parseWhereClauseSimple(string $whereClause, array $parameters): array
    {
        $conditions = [];

        // Handle IN clause with parameter: WHERE column IN (:param)
        if (preg_match('/(\w+)\s+IN\s*\(\s*(:?\w+)\s*\)/i', $whereClause, $matches)) {
            $column = $matches[1];
            $paramKey = $matches[2];

            if (strpos($paramKey, ':') === 0 && isset($parameters[$paramKey])) {
                $value = $parameters[$paramKey];
                $conditions[] = [
                    'column' => $column,
                    'operator' => 'IN',
                    'value' => is_array($value) ? $value : [$value]
                ];
            }
            return $conditions;
        }

        // Handle simple conditions: column = :value AND column2 = :value2
        $parts = preg_split('/\s+AND\s+/i', $whereClause);

        foreach ($parts as $part) {
            $part = trim($part);

            if (preg_match('/(\w+)\s*(=|!=|<>|>|<|>=|<=)\s*(:?\w+)/', $part, $matches)) {
                $column = $matches[1];
                $operator = $matches[2];
                $value = $matches[3];

                if (strpos($value, ':') === 0) {
                    // Named parameter
                    $conditions[] = [
                        'column' => $column,
                        'operator' => $operator,
                        'value' => $parameters[$value] ?? null
                    ];
                } else {
                    // Literal value
                    $conditions[] = [
                        'column' => $column,
                        'operator' => $operator,
                        'value' => trim($value, "'\"")
                    ];
                }
            }
        }

        return $conditions;
    }

    /**
     * Execute a statement.
     *
     * @param mixed ...$params The parameters.
     * @return mixed
     */
    public function exec(mixed ...$params): mixed
    {
        if (isset($params[0]) && is_array($params[0])) {
            $this->setQueryParameters($params[0]);
        }

        // Process based on query type (determined by QueryBuilder)
        return $this->affectedRows;
    }
}
