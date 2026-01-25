<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\JSON\Connection\Fetch;

use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IFetchStrategy;
use GenericDatabase\Interfaces\Connection\IFlatFileFetch;
use GenericDatabase\Interfaces\Connection\IStructure;
use GenericDatabase\Abstract\AbstractFlatFileFetch;
use GenericDatabase\Engine\JSON\Connection\JSON;
use GenericDatabase\Engine\JSON\QueryBuilder\Regex;
use GenericDatabase\Generic\FlatFiles\DataProcessor;

/**
 * Handles fetch operations for JSON connections.
 * Extends AbstractFlatFileFetch to leverage common flat-file fetch functionality.
 *
 * @package GenericDatabase\Engine\JSON\Connection\Fetch
 */
class FetchHandler extends AbstractFlatFileFetch implements IFlatFileFetch
{
    /**
     * Constructor.
     *
     * @param IConnection $instance The connection instance.
     * @param IFetchStrategy|null $strategy The fetch strategy (optional).
     * @param IStructure|null $structureHandler Structure handler.
     */
    public function __construct(
        IConnection $instance,
        ?IFetchStrategy $strategy = null,
        ?IStructure $structureHandler = null
    ) {
        // Create a default strategy if none provided
        $strategy = $strategy ?? new Strategy\FetchStrategy();
        parent::__construct($instance, $strategy, $structureHandler);
    }

    /**
     * Execute a stored query and return the result set.
     * Implementation of abstract method from AbstractFlatFileFetch.
     *
     * @return array The result set.
     */
    protected function executeStoredQuery(): array
    {
        // Get the stored query string and parameters
        $queryString = $this->getInstance()->getQueryString();
        $queryParameters = $this->getInstance()->getQueryParameters();

        // If no query is stored, return raw data from structure handler
        if (empty($queryString)) {
            $structureHandler = $this->getStructureHandler();
            if ($structureHandler !== null) {
                return $structureHandler->getData();
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

            $this->getInstance()->setQueryRows($rowCount);
            $this->getInstance()->setQueryColumns($columnCount);
            $this->getInstance()->setAffectedRows(0);

            return $result;
        } catch (\Exception $e) {
            // Re-throw exception to help debugging - don't silently fail
            throw $e;
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
     * Remove double quotes from identifiers in SQL query for parsing.
     * This allows regex patterns to work with queries that have quoted identifiers.
     * Only removes quotes from identifiers (table names, column names, aliases),
     * not from string literals.
     *
     * @param string $query The SQL query with quoted identifiers.
     * @return string The query with quotes removed from identifiers only.
     */
    private function unquoteIdentifiers(string $query): string
    {
        // Remove double quotes from identifiers (table names, column names, aliases)
        // Pattern matches: "identifier" where identifier is a valid SQL identifier
        // This will match: "table", "column", "alias", "table"."column", etc.
        // But won't match string literals in WHERE clauses (those are handled separately)
        
        // Replace quoted identifiers - matches word characters between double quotes
        // that are not inside single-quoted strings
        $result = '';
        $len = strlen($query);
        $inSingleQuote = false;
        $inDoubleQuote = false;
        $i = 0;
        
        while ($i < $len) {
            $char = $query[$i];
            
            // Track single quotes (string literals) - don't process inside them
            if ($char === "'" && ($i === 0 || $query[$i - 1] !== '\\')) {
                $inSingleQuote = !$inSingleQuote;
                $result .= $char;
                $i++;
                continue;
            }
            
            // Process double quotes (identifiers)
            if ($char === '"' && !$inSingleQuote) {
                // Find the matching closing quote
                $start = $i;
                $i++;
                $identifier = '';
                
                while ($i < $len && $query[$i] !== '"') {
                    $identifier .= $query[$i];
                    $i++;
                }
                
                // If we found a closing quote and it's a valid identifier
                if ($i < $len && preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $identifier)) {
                    // Remove the quotes - just add the identifier
                    $result .= $identifier;
                    $i++; // Skip the closing quote
                } else {
                    // Not a valid identifier or unmatched quote, keep as is
                    $result .= substr($query, $start, $i - $start + ($i < $len ? 1 : 0));
                    if ($i < $len) {
                        $i++;
                    }
                }
            } else {
                $result .= $char;
                $i++;
            }
        }
        
        return $result;
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

        // Get data context handler
        $structureHandler = $this->getStructureHandler();

        // Remove quotes from identifiers for parsing (but keep original query for display)
        $queryForParsing = $this->unquoteIdentifiers($query);

        // Extract table name from FROM clause and load data
        $tableName = null;
        // Updated regex to handle both quoted and unquoted table names
        if (preg_match('/\bFROM\s+(?:"?(\w+)"?)(?:\s+(?:"?(\w+)"?))?/i', $queryForParsing, $matches)) {
            // First match is table name, second is alias (if present)
            $tableName = $matches[1];
            if ($structureHandler !== null) {
                $structureHandler->load($tableName);
            }
        }

        // Get data from data context handler
        $data = $structureHandler !== null ? $structureHandler->getData() : [];

        // Create DataProcessor instance
        $processor = new DataProcessor($data);

        // Extract and apply WHERE clause
        if (preg_match('/\bWHERE\s+(.*?)(?:\s+ORDER\s+BY|\s+GROUP\s+BY|\s+LIMIT|$)/is', $queryForParsing, $matches)) {
            $whereClause = trim($matches[1]);
            $conditions = $this->parseWhereClause($whereClause);
            if (!empty($conditions)) {
                $processor->where($conditions, 'AND');
            }
        }

        // Extract and apply ORDER BY clause
        if (preg_match('/\bORDER\s+BY\s+(.*?)(?:\s+LIMIT|$)/is', $queryForParsing, $matches)) {
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
        if (preg_match('/\bLIMIT\s+(\d+)(?:\s*,\s*(\d+))?/i', $queryForParsing, $matches)) {
            if (isset($matches[2])) {
                $processor->limit((int) $matches[2], (int) $matches[1]);
            } else {
                $processor->limit((int) $matches[1]);
            }
        }

        // Get filtered data
        $result = $processor->getData();

        // Extract SELECT columns and apply aliases
        // Use original query to preserve aliases with quotes, but unquote for processing
        if (preg_match('/^SELECT\s+(.*?)\s+FROM/is', $queryForParsing, $matches)) {
            $columns = trim($matches[1]);
            if ($columns !== '*') {
                $columnParts = array_map('trim', explode(',', $columns));
                // Remove quotes from column parts for processing
                $columnParts = array_map(function($col) {
                    return $this->unquoteIdentifiers($col);
                }, $columnParts);
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

        // Remove quotes from identifiers in WHERE clause for parsing
        $whereClause = $this->unquoteIdentifiers($whereClause);

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
                        $values = array_map(function ($val) {
                            return trim(trim($val), "'\"");
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
     * @param array $columns The column specifications (already unquoted).
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
                    // Handle table.column AS alias format
                    $originalColumnExpr = trim($matches[1]);
                    $alias = trim($matches[2], '"\'`');

                    // Extract column name from table.column or just column
                    if (preg_match('/^(\w+)\.(\w+)$/', $originalColumnExpr, $colMatches)) {
                        // Table.column format - use just the column name
                        $originalColumn = $colMatches[2];
                    } else {
                        // Just column name
                        $originalColumn = trim($originalColumnExpr, '"\'`');
                    }

                    if (isset($row[$originalColumn])) {
                        $result[$alias] = $row[$originalColumn];
                    }
                } else {
                    // No alias, check if it's table.column format
                    if (preg_match('/^(\w+)\.(\w+)$/', $col, $colMatches)) {
                        // Table.column format - use just the column name
                        $cleanCol = $colMatches[2];
                    } else {
                        // Just column name
                        $cleanCol = trim($col, '"\'`');
                    }
                    
                    if (isset($row[$cleanCol])) {
                        $result[$cleanCol] = $row[$cleanCol];
                    }
                }
            }
            return $result;
        }, $data);
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
        $fetch = $fetchStyle ?? JSON::FETCH_ASSOC;

        return match ($fetch) {
            JSON::FETCH_OBJ => $this->internalFetchAssoc() !== false ? (object) $this->getResultSet()[$this->cursor - 1] : false,
            JSON::FETCH_INTO => $this->fetchIntoObject($fetchArgument),
            JSON::FETCH_CLASS => $this->internalFetchClass($optArgs, $fetchArgument ?? '\stdClass'),
            JSON::FETCH_COLUMN => $this->internalFetchColumn($fetchArgument ?? 0),
            JSON::FETCH_ASSOC => $this->internalFetchAssoc(),
            JSON::FETCH_NUM => $this->internalFetchNum(),
            default => $this->internalFetchBoth(),
        };
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
        $fetch = $fetchStyle ?? JSON::FETCH_ASSOC;

        return match ($fetch) {
            JSON::FETCH_OBJ => array_map(fn($row) => (object) $row, $this->internalFetchAllAssoc()),
            JSON::FETCH_INTO => $this->fetchAllIntoObjects($fetchArgument),
            JSON::FETCH_CLASS => $this->internalFetchAllClass($optArgs, $fetchArgument ?? '\stdClass'),
            JSON::FETCH_COLUMN => $this->internalFetchAllColumn($fetchArgument ?? 0),
            JSON::FETCH_ASSOC => $this->internalFetchAllAssoc(),
            JSON::FETCH_NUM => $this->internalFetchAllNum(),
            default => $this->internalFetchAllBoth(),
        };
    }

    /**
     * Fetch row into an existing object.
     *
     * @param object|null $object The object to populate.
     * @return object|false The populated object or false.
     */
    private function fetchIntoObject(?object $object): object|false
    {
        $row = $this->internalFetchAssoc();
        if ($row === false) {
            return false;
        }
        return $this->fetchInto($row, $object);
    }

    /**
     * Fetch all rows into objects.
     *
     * @param object|null $object The object template.
     * @return array The populated objects.
     */
    private function fetchAllIntoObjects(?object $object): array
    {
        $results = $this->internalFetchAllAssoc();
        return array_map(fn($row) => $this->fetchInto($row, clone $object), $results);
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
        $index = 0;
        $len = strlen($clause);
        $inBetween = false;

        while ($index < $len) {
            // Check for BETWEEN keyword (start of BETWEEN...AND construct)
            if (!$inBetween && preg_match('/\bBETWEEN\b/i', substr($clause, $index, 7))) {
                $inBetween = true;
                $current .= substr($clause, $index, 7);
                $index += 7;
                continue;
            }

            // Check for AND keyword
            if (strtoupper(substr($clause, $index, 3)) === 'AND') {
                // Check if this is a word boundary
                $beforeOk = ($index === 0) || !ctype_alnum($clause[$index - 1]);
                $afterOk = ($index + 3 >= $len) || !ctype_alnum($clause[$index + 3]);

                if ($beforeOk && $afterOk) {
                    if ($inBetween) {
                        // This AND is part of BETWEEN...AND, include it
                        $current .= 'AND';
                        $index += 3;
                        $inBetween = false; // BETWEEN...AND complete
                        continue;
                    } else {
                        // This is a logical AND separator
                        if (!empty(trim($current))) {
                            $parts[] = trim($current);
                        }
                        $current = '';
                        $index += 3;
                        // Skip whitespace after AND
                        while ($index < $len && ctype_space($clause[$index])) {
                            $index++;
                        }
                        continue;
                    }
                }
            }

            $current .= $clause[$index];
            $index++;
        }

        if (!empty(trim($current))) {
            $parts[] = trim($current);
        }

        return !empty($parts) ? $parts : [$clause];
    }
}
