<?php

namespace GenericDatabase\Abstract\Statements;

use GenericDatabase\Generic\Statements\Metadata;
use GenericDatabase\Interfaces\IConnection;
use AllowDynamicProperties;

#[AllowDynamicProperties]
abstract class AbstractStatements
{
    protected mixed $statement = null;
    protected Metadata $metadata;
    protected static IConnection $connection;

    /**
     * Constructor for AbstractStatements.
     *
     * @param IConnection $connection The connection instance to be used for database interactions.
     */
    public function __construct(IConnection $connection)
    {
        self::$connection = $connection;
        $this->metadata = new Metadata();
    }

    public function getInstance(): IConnection
    {
        return self::$connection;
    }

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

    public function getAllMetadata(): Metadata
    {
        return $this->metadata;
    }

    public function getQueryString(): string
    {
        return $this->metadata->getQuery()->getString();
    }

    public function setQueryString(string $queryString): void
    {
        $this->metadata->getQuery()->setString($queryString);
    }

    public function getQueryParameters(): ?array
    {
        return $this->metadata->getQuery()->getArguments();
    }

    public function setQueryParameters(?array $queryParameters): void
    {
        $this->metadata->getQuery()->setArguments($queryParameters);
    }

    public function getQueryRows(): int|false
    {
        return $this->metadata->getQuery()->getRows()->getFetched();
    }

    public function setQueryRows(callable|int|false $queryRows): void
    {
        if (is_int($queryRows)) {
            $this->metadata->getQuery()->getRows()->setFetched($queryRows);
        }
    }

    public function getQueryColumns(): int|false
    {
        return $this->metadata->getQuery()->getColumns();
    }

    public function setQueryColumns(int|false $queryColumns): void
    {
        if (is_int($queryColumns)) {
            $this->metadata->getQuery()->setColumns($queryColumns);
        }
    }

    public function getAffectedRows(): int|false
    {
        return $this->metadata->getQuery()->getRows()->getAffected();
    }

    public function setAffectedRows(int|false $affectedRows): void
    {
        if (is_int($affectedRows)) {
            $this->metadata->getQuery()->getRows()->setAffected($affectedRows);
        }
    }

    public function getStatement(): mixed
    {
        return $this->statement;
    }

    public function setStatement(mixed $statement): void
    {
        $this->statement = $statement;
    }
}
