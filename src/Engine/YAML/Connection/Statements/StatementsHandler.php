<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\YAML\Connection\Statements;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Interfaces\Connection\IFlatFileStatements;
use GenericDatabase\Abstract\AbstractFlatFileStatements;
use GenericDatabase\Helpers\Exceptions;
use GenericDatabase\Generic\FlatFiles\DataProcessor;
use GenericDatabase\Helpers\Types\Compounds\Arrays;
use GenericDatabase\Helpers\Parsers\SQL;
use GenericDatabase\Helpers\Parsers\Schema;
use GenericDatabase\Engine\YAML\Connection\Structure\StructureHandler;
use GenericDatabase\Engine\YAML\Connection\Options\OptionsHandler;
use GenericDatabase\Engine\YAML\Connection\Report\ReportHandler;

/**
 * Handles SQL-like statement operations for YAML connections.
 * Extends AbstractFlatFileStatements to leverage common flat-file functionality.
 *
 * @package GenericDatabase\Engine\YAML\Connection\Statements
 */
class StatementsHandler extends AbstractFlatFileStatements implements IFlatFileStatements
{
    /**
     * @var IConnection The connection instance.
     */
    private IConnection $connection;

    /**
     * @var StructureHandler|null Structure handler; strategy is obtained via getStructureStrategy() for DML.
     */
    private ?StructureHandler $structureHandler;

    /**
     * Constructor.
     *
     * @param IConnection $instance The connection instance.
     * @param StructureHandler|null $structureHandler Structure handler; strategy from getStructureStrategy() is used for INSERT/UPDATE/DELETE.
     * @param IOptions|null $optionsHandler The options handler (optional).
     * @param IReport|null $reportHandler The report handler (optional).
     */
    public function __construct(
        IConnection $instance,
        ?StructureHandler $structureHandler = null,
        ?IOptions $optionsHandler = null,
        ?IReport $reportHandler = null
    ) {
        $this->connection = $instance;
        $this->structureHandler = $structureHandler;
        // Create default handlers if not provided
        $optionsHandler = $optionsHandler ?? new OptionsHandler($instance);
        $reportHandler = $reportHandler ?? new ReportHandler($instance);

        parent::__construct($instance, $optionsHandler, $reportHandler);
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
     * Load table data via structure handler (strategy when available, otherwise handler directly).
     *
     * @param string $tableName The table name.
     * @return array
     */
    private function loadTableData(string $tableName): array
    {
        if ($this->structureHandler === null) {
            return [];
        }
        $strategy = $this->structureHandler->getStructureStrategy();
        if ($strategy !== null) {
            $strategy->load($tableName);
            return $strategy->getData();
        }
        $this->structureHandler->load($tableName);
        return $this->structureHandler->getData();
    }

    /**
     * Persist table data via structure handler (strategy when available, otherwise handler directly).
     *
     * @param array $newData The data to save.
     * @param string $tableName The table name.
     * @return void
     */
    private function persistTableData(array $newData, string $tableName): void
    {
        if ($this->structureHandler === null) {
            return;
        }
        $strategy = $this->structureHandler->getStructureStrategy();
        if ($strategy !== null) {
            $strategy->setData($newData);
            $strategy->save($newData, $tableName);
            return;
        }
        $this->structureHandler->setData($newData);
        $this->structureHandler->save($newData, $tableName);
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
        $this->setAllMetadata();
        $this->setQueryString($query);
        $this->setStatement($query);
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

        $data = $this->loadTableData($tableName);

        $directory = $this->structureHandler !== null ? (string) $this->structureHandler->get('database') : '';
        $primaryKey = Schema::getPrimaryKeyForTable($directory, $tableName, $data);

        // Build the row to insert
        $row = [];
        foreach ($columns as $index => $column) {
            $row[$column] = $values[$index] ?? null;
        }

        // Generate auto-increment for primary key column if not provided
        if (!isset($row[$primaryKey]) || $row[$primaryKey] === null) {
            $maxId = 0;
            foreach ($data as $existingRow) {
                $existingRow = (array) $existingRow;
                if (isset($existingRow[$primaryKey]) && (int) $existingRow[$primaryKey] > $maxId) {
                    $maxId = (int) $existingRow[$primaryKey];
                }
            }
            $row[$primaryKey] = $maxId + 1;
        }

        $processor = new DataProcessor($data);
        $result = $processor->insert($row);

        if ($result) {
            $newData = $processor->getData();
            $this->persistTableData($newData, $tableName);
            $this->setLastInsertId((int) $row[$primaryKey]);
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

        $data = $this->loadTableData($tableName);

        // Parse SET clause (raw values)
        $updateData = $this->parseRawSetClause($setClause);

        // Parse WHERE clause (raw values)
        $conditions = $this->parseRawWhereClause($whereClause);

        $processor = new DataProcessor($data);
        $affected = $processor->update($updateData, $conditions, 'AND');

        if ($affected > 0) {
            $newData = $processor->getData();
            $this->persistTableData($newData, $tableName);
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

        $data = $this->loadTableData($tableName);

        // Parse WHERE clause (raw values)
        $conditions = $this->parseRawWhereClause($whereClause);

        $processor = new DataProcessor($data);
        $deleted = $processor->delete($conditions, 'AND');

        if ($deleted > 0) {
            $newData = $processor->getData();
            $this->persistTableData($newData, $tableName);
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
        $this->setAllMetadata();

        // Parse the query to format with proper identifier quoting
        $parsedQuery = $this->parse($query);
        $this->setQueryString($parsedQuery);
        $this->setStatement($parsedQuery);

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
        // Use original query for detection and execution to avoid issues with quoted identifiers
        $queryType = $this->detectQueryType($query);

        if (in_array($queryType, ['INSERT', 'UPDATE', 'DELETE'])) {
            // For DML operations, we need to work with the original query format
            // since the parsing logic expects unquoted identifiers
            $this->executeDmlQuery($query, $parameters, $isMulti);
        }

        return $this->connection;
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

        $data = $this->loadTableData($tableName);

        $directory = $this->structureHandler !== null ? (string) $this->structureHandler->get('database') : '';
        $primaryKey = Schema::getPrimaryKeyForTable($directory, $tableName, $data);

        // Build the row to insert
        $row = [];
        foreach ($columns as $index => $column) {
            $placeholder = $valuePlaceholders[$index] ?? null;
            if ($placeholder && preg_match('/^:(\w+)$/', $placeholder, $matches)) {
                $paramKey = ':' . $matches[1];
                $row[$column] = $parameters[$paramKey] ?? null;
            }
        }

        // Generate auto-increment for primary key column if not provided
        if (!isset($row[$primaryKey]) || $row[$primaryKey] === null) {
            $maxId = 0;
            foreach ($data as $existingRow) {
                $existingRow = (array) $existingRow;
                if (isset($existingRow[$primaryKey]) && (int) $existingRow[$primaryKey] > $maxId) {
                    $maxId = (int) $existingRow[$primaryKey];
                }
            }
            $row[$primaryKey] = $maxId + 1;
        }

        $processor = new DataProcessor($data);
        $result = $processor->insert($row);

        if ($result) {
            $newData = $processor->getData();
            $this->persistTableData($newData, $tableName);
            $this->setLastInsertId((int) $row[$primaryKey]);
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

        $data = $this->loadTableData($tableName);

        // Parse SET clause
        $updateData = $this->parseSetClause($setClause, $parameters);

        // Parse WHERE clause
        $conditions = $this->parseWhereClauseSimple($whereClause, $parameters);

        $processor = new DataProcessor($data);
        $affected = $processor->update($updateData, $conditions, 'AND');

        if ($affected > 0) {
            $newData = $processor->getData();
            $this->persistTableData($newData, $tableName);
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

        $data = $this->loadTableData($tableName);

        // Parse WHERE clause
        $conditions = $this->parseWhereClauseSimple($whereClause, $parameters);

        $processor = new DataProcessor($data);
        $deleted = $processor->delete($conditions, 'AND');

        if ($deleted > 0) {
            $newData = $processor->getData();
            $this->persistTableData($newData, $tableName);
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
        return $this->getAffectedRows();
    }

    /**
     * Parses an SQL statement and returns an statement.
     * Uses SQL::escape() to format the query with proper identifier quoting,
     * matching the behavior of SQLiteConnection.
     *
     * @param mixed ...$params The parameters for the query function.
     * @return string The statement resulting from the SQL statement.
     */
    public function parse(mixed ...$params): string
    {
        $query = reset($params);
        if (empty($query)) {
            $this->setQueryString('');
            return '';
        }

        // Use SQL::escape() with DOUBLE_QUOTE dialect to match SQLiteConnection behavior
        $parsedQuery = SQL::escape((string) $query, SQL::SQL_DIALECT_DOUBLE_QUOTE);
        $this->setQueryString($parsedQuery);
        return $parsedQuery;
    }
}
