<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\JSON\Connection\Statements;

use GenericDatabase\Interfaces\Connection\IStatements;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Engine\FlatFile\DataProcessor;
use GenericDatabase\Helpers\Exceptions;

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
        $metadata->query->rows->setFetched($this->queryRows);
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
    public function setLastInsertId(int $id): void
    {
        $this->lastInsertId = $id;
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

        // The actual execution is handled by the QueryBuilder
        // This method just stores the query for later execution
        $this->statement = $query;

        return $this->connection;
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
        $this->statement = $query;

        // Process parameters based on input format
        $parameters = [];

        if (count($params) > 1) {
            // Check if second parameter is an array (named parameters)
            if (is_array($params[1])) {
                $parameters = $params[1];
            } else {
                // Extract positional parameters from remaining arguments
                // Build a mapping from named placeholders to values
                $placeholders = [];
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
        return $this->connection;
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
