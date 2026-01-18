<?php

declare(strict_types=1);

namespace GenericDatabase\Engine\CSV\Connection\Statements;

use GenericDatabase\Interfaces\Connection\IStatements;
use GenericDatabase\Interfaces\IConnection;

/**
 * Handles SQL-like statement operations for CSV connections.
 *
 * @package GenericDatabase\Engine\CSV\Connection\Statements
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
     * Constructor.
     *
     * @param IConnection $connection The connection instance.
     */
    public function __construct(IConnection $connection)
    {
        $this->connection = $connection;
    }

    public function setAllMetadata(): void
    {
        $this->queryRows = 0;
        $this->queryColumns = 0;
        $this->affectedRows = 0;
    }

    public function getAllMetadata(): object
    {
        return (object) [
            'queryRows' => $this->queryRows,
            'queryColumns' => $this->queryColumns,
            'affectedRows' => $this->affectedRows
        ];
    }

    public function getQueryString(): string
    {
        return $this->queryString;
    }

    public function setQueryString(string $params): void
    {
        $this->queryString = $params;
    }

    public function getQueryParameters(): ?array
    {
        return $this->queryParameters;
    }

    public function setQueryParameters(?array $params): void
    {
        $this->queryParameters = $params;
    }

    public function getQueryRows(): int|false
    {
        return $this->queryRows;
    }

    public function setQueryRows(callable|int|false $params): void
    {
        $this->queryRows = is_callable($params) ? $params() : $params;
    }

    public function getQueryColumns(): int|false
    {
        return $this->queryColumns;
    }

    public function setQueryColumns(int|false $params): void
    {
        $this->queryColumns = $params;
    }

    public function getAffectedRows(): int|false
    {
        return $this->affectedRows;
    }

    public function setAffectedRows(int|false $params): void
    {
        $this->affectedRows = $params;
    }

    public function getStatement(): mixed
    {
        return $this->statement;
    }

    public function setStatement(mixed $statement): void
    {
        $this->statement = $statement;
    }

    public function bindParam(object $params): void
    {
    }

    public function parse(mixed ...$params): string
    {
        return $params[0] ?? '';
    }

    public function lastInsertId(?string $name = null): string|int|false
    {
        return $this->lastInsertId;
    }

    public function setLastInsertId(int $id): void
    {
        $this->lastInsertId = $id;
    }

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

    public function query(mixed ...$params): ?IConnection
    {
        $query = $params[0] ?? '';
        $this->setQueryString($query);
        $this->setAllMetadata();
        $this->statement = $query;
        return $this->connection;
    }

    public function prepare(mixed ...$params): ?IConnection
    {
        $query = $params[0] ?? '';
        $this->setQueryString($query);
        $this->statement = $query;
        return $this->connection;
    }

    public function exec(mixed ...$params): mixed
    {
        if (isset($params[0]) && is_array($params[0])) {
            $this->setQueryParameters($params[0]);
        }
        return $this->affectedRows;
    }
}

