<?php

namespace GenericDatabase\Abstract;

use GenericDatabase\Shared\Run;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\Connection\IStatementsAbstract;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Generic\Statements\Metadata;

/**
 *
 * The `GenericDatabase\Abstract\AbstractStatements` that handles database statements.
 * It implements the `IStatements` interface and provides a set of methods for managing database connections, options, reports, and metadata.
 *
 * Main functionalities:
 * - Provides a base class for managing database statements.
 * - Allows dynamic setting and getting of options using a connection instance and an options array.
 * - Provides a common interface for all options classes.
 *
 * Methods:
 * - `getInstance`: Returns the current database connection instance.
 * - `set`: Sets a value using a dynamic method call on the database connection instance.
 * - `get`: Gets a value using a dynamic method call on the database connection instance.
 * - `getOptionsHandler`: Returns the current options handler instance.
 * - `getReportHandler`: Returns the current report handler instance.
 * - `setAllMetadata`: Resets all metadata to default values.
 * - `getAllMetadata`: Returns the current metadata instance.
 * - `getQueryString`: Returns the current query string.
 * - `setQueryString`: Sets the query string.
 * - `getQueryParameters`: Returns the current query parameters.
 * - `setQueryParameters`: Sets the query parameters.
 * - `getQueryRows`: Returns the number of rows fetched.
 * - `setQueryRows`: Sets the number of rows fetched.
 * - `getQueryColumns`: Returns the number of columns in the result.
 * - `setQueryColumns`: Sets the number of columns in the result.
 * - `getAffectedRows`: Returns the number of affected rows.
 * - `setAffectedRows`: Sets the number of affected rows.
 * - `getStatement`: Returns the current statement object.
 * - `setStatement`: Sets the statement object.
 *
 * Fields:
 * - `$instance`: The connection instance used for dynamic operations.
 * - `$optionsHandler`: The options handler for managing configuration.
 * - `$reportHandler`: The report handler for managing configuration.
 * - `$metadata`: The metadata handler for managing configuration.
 * - `$statement`: The statement handler for managing configuration.
 *
 * Note that some of these methods are used to manage metadata, which is an instance of the `Metadata` class. The metadata is used to store information about the query,  such as the query string, parameters, rows fetched, and columns.
 *
 * @package PHP-Generic-Database
 * @subpackage Abstract
 * @category Database
 * @abstract
 */
abstract class AbstractStatements implements IStatementsAbstract
{
    /** @var IConnection Database connection instance */
    protected static IConnection $instance;

    /** @var IOptions Options handler instance */
    protected static IOptions $optionsHandler;

    /** @var IReport Report handler instance */
    protected static IReport $reportHandler;

    /** @var Metadata Metadata handler for queries */
    protected Metadata $metadata;

    /** @var mixed|null Current statement object */
    protected mixed $statement = null;

    /**
     * Initialize statements with required handlers
     *
     * @param IConnection $instance Database connection instance
     * @param IOptions $optionsHandler Options handler instance
     * @param IReport $reportHandler Report handler instance
     */
    public function __construct(IConnection $instance, IOptions $optionsHandler, IReport $reportHandler)
    {
        self::$instance = $instance;
        self::$optionsHandler = $optionsHandler;
        self::$reportHandler = $reportHandler;
        $this->metadata = new Metadata();
    }

    /**
     * Get the database connection instance
     *
     * @return IConnection Current connection instance
     */
    public static function getInstance(): IConnection
    {
        return self::$instance;
    }

    /**
     * Set a value using dynamic method call
     *
     * @param string $name Name of property to set
     * @param mixed $value Value to set
     */
    public function set(string $name, mixed $value): void
    {
        Run::call([$this->getInstance(), 'set' . ucfirst($name)], $value);
    }

    /**
     * Get a value using dynamic method call
     *
     * @param string $name Name of property to get
     * @return mixed Retrieved value
     */
    public function get(string $name): mixed
    {
        return Run::call([$this->getInstance(), 'get' . ucfirst($name)]);
    }

    /**
     * Get the options handler instance
     *
     * @return IOptions Current options handler
     */
    public function getOptionsHandler(): IOptions
    {
        return self::$optionsHandler;
    }

    /**
     * Get the report handler instance
     *
     * @return IReport Current report handler
     */
    public function getReportHandler(): IReport
    {
        return self::$reportHandler;
    }

    /**
     * Reset all metadata to default values
     */
    public function setAllMetadata(): void
    {
        $this->metadata->getQuery()
            ->setString('')
            ->setArguments([])
            ->setColumns(0);
        $this->metadata->getQuery()->getRows()
            ->setFetched(0)
            ->setAffected(0);
    }

    /**
     * Get all metadata
     *
     * @return Metadata Current metadata instance
     */
    public function getAllMetadata(): Metadata
    {
        return $this->metadata;
    }

    /**
     * Converts aggregate function results in a row to proper types based on column names.
     *
     * @param array $row The row data
     * @param array|null $aggregateColumns Optional pre-detected aggregate columns map [columnName => aggType]
     * @return array The row with converted types
     */
    protected function convertAggregateTypesInRow(array $row, ?array $aggregateColumns = null): array
    {
        if ($aggregateColumns === null) {
            // Detect aggregate columns from row keys
            $aggregateColumns = [];
            foreach (array_keys($row) as $key) {
                $keyUpper = strtoupper($key);
                if (preg_match('/\b(COUNT|SUM|AVG|MIN|MAX)\b/i', $key, $matches)) {
                    $aggregateColumns[$key] = strtoupper($matches[1]);
                } elseif (str_contains($keyUpper, 'SOMA') || (str_contains($keyUpper, 'SUM') && !str_contains($keyUpper, 'TOTAL'))) {
                    $aggregateColumns[$key] = 'SUM';
                } elseif (str_contains($keyUpper, 'MEDIA') || str_contains($keyUpper, 'AVERAGE') || str_contains($keyUpper, 'AVG')) {
                    $aggregateColumns[$key] = 'AVG';
                } elseif (str_contains($keyUpper, 'COUNT') || str_contains($keyUpper, 'TOTAL')) {
                    $aggregateColumns[$key] = 'COUNT';
                } elseif (str_contains($keyUpper, 'MIN')) {
                    $aggregateColumns[$key] = 'MIN';
                } elseif (str_contains($keyUpper, 'MAX')) {
                    $aggregateColumns[$key] = 'MAX';
                }
            }
        }

        foreach ($row as $key => $value) {
            if (isset($aggregateColumns[$key]) && $value !== null) {
                $aggType = $aggregateColumns[$key];
                $valueStr = (string) $value;
                if (is_numeric($value) || (is_string($value) && $valueStr !== '' && is_numeric($valueStr))) {
                    switch ($aggType) {
                        case 'COUNT':
                        case 'SUM':
                            $row[$key] = (int) $value;
                            break;
                        case 'AVG':
                            $row[$key] = (float) $value;
                            break;
                        case 'MIN':
                        case 'MAX':
                            $row[$key] = str_contains($valueStr, '.') ? (float) $value : (int) $value;
                            break;
                    }
                }
            }
        }
        return $row;
    }

    /**
     * Converts numeric field types in a row based on field type metadata.
     *
     * @param array $row The row data
     * @param array $numericFields Map of field names to their types ['fieldName' => 'INTEGER'|'FLOAT'|'NUMBER']
     * @return array The row with converted types
     */
    protected function convertNumericTypesInRow(array $row, array $numericFields): array
    {
        foreach ($row as $key => $value) {
            if (isset($numericFields[$key]) && $value !== null) {
                // Only convert if value is a string that represents a number
                // Skip if already the correct type (int or float)
                if (is_string($value)) {
                    $valueStr = trim($value);
                    if ($valueStr !== '' && is_numeric($valueStr)) {
                        $fieldType = $numericFields[$key];
                        if ($fieldType === 'INTEGER' || $fieldType === 'INT' || $fieldType === 'BIGINT' || $fieldType === 'SMALLINT' || $fieldType === 'SERIAL' || $fieldType === 'BIGSERIAL' || $fieldType === 'OID') {
                            $row[$key] = (int) $value;
                        } elseif ($fieldType === 'FLOAT' || $fieldType === 'DOUBLE' || $fieldType === 'REAL' || $fieldType === 'NUMERIC' || $fieldType === 'DECIMAL' || $fieldType === 'MONEY') {
                            $row[$key] = (float) $value;
                        } elseif ($fieldType === 'NUMBER') {
                            // Oracle NUMBER type - check if it has decimal places
                            $row[$key] = str_contains($valueStr, '.') ? (float) $value : (int) $value;
                        }
                    }
                } elseif (!is_int($value) && !is_float($value)) {
                    // If it's not a string and not already numeric, try to convert
                    $valueStr = (string) $value;
                    if (is_numeric($valueStr)) {
                        $fieldType = $numericFields[$key];
                        if ($fieldType === 'INTEGER' || $fieldType === 'INT' || $fieldType === 'BIGINT' || $fieldType === 'SMALLINT' || $fieldType === 'SERIAL' || $fieldType === 'BIGSERIAL' || $fieldType === 'OID') {
                            $row[$key] = (int) $value;
                        } elseif ($fieldType === 'FLOAT' || $fieldType === 'DOUBLE' || $fieldType === 'REAL' || $fieldType === 'NUMERIC' || $fieldType === 'DECIMAL' || $fieldType === 'MONEY') {
                            $row[$key] = (float) $value;
                        } elseif ($fieldType === 'NUMBER') {
                            $row[$key] = str_contains($valueStr, '.') ? (float) $value : (int) $value;
                        }
                    }
                }
            }
        }
        return $row;
    }

    /**
     * Get the current query string
     *
     * @return string Current query string
     */
    public function getQueryString(): string
    {
        return $this->metadata->getQuery()->getString();
    }

    /**
     * Set the query string
     *
     * @param string $params Query string to set
     */
    public function setQueryString(string $params): void
    {
        $this->metadata->getQuery()->setString($params);
    }

    /**
     * Get the current query parameters
     *
     * @return array|null Current query parameters
     */
    public function getQueryParameters(): ?array
    {
        return $this->metadata->getQuery()->getArguments();
    }

    /**
     * Set the query parameters
     *
     * @param array|null $params Parameters to set
     */
    public function setQueryParameters(?array $params): void
    {
        $this->metadata->getQuery()->setArguments($params);
    }

    /**
     * Get number of rows fetched
     *
     * @return int|false Number of rows or false on failure
     */
    public function getQueryRows(): int|false
    {
        return $this->metadata->getQuery()->getRows()->getFetched();
    }

    /**
     * Set number of rows fetched
     *
     * @param callable|int|false $params Number of rows to set
     */
    public function setQueryRows(callable|int|false $params): void
    {
        if (is_int($params)) {
            $this->metadata->getQuery()->getRows()->setFetched($params);
        }
    }

    /**
     * Get number of columns in result
     *
     * @return int|false Number of columns or false on failure
     */
    public function getQueryColumns(): int|false
    {
        return $this->metadata->getQuery()->getColumns();
    }

    /**
     * Set number of columns in result
     *
     * @param int|false $params Number of columns to set
     */
    public function setQueryColumns(int|false $params): void
    {
        if (is_int($params)) {
            $this->metadata->getQuery()->setColumns($params);
        }
    }

    /**
     * Get number of affected rows
     *
     * @return int|false Number of affected rows or false on failure
     */
    public function getAffectedRows(): int|false
    {
        return $this->metadata->getQuery()->getRows()->getAffected();
    }

    /**
     * Set number of affected rows
     *
     * @param int|false $params Number of affected rows to set
     */
    public function setAffectedRows(int|false $params): void
    {
        if (is_int($params)) {
            $this->metadata->getQuery()->getRows()->setAffected($params);
        }
    }

    /**
     * Get current statement object
     *
     * @return mixed Current statement
     */
    public function getStatement(): mixed
    {
        return $this->statement;
    }

    /**
     * Set statement object
     *
     * @param mixed $statement Statement to set
     */
    public function setStatement(mixed $statement): void
    {
        $this->statement = $statement;
    }
}
