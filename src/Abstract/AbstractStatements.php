<?php

namespace GenericDatabase\Abstract;

use AllowDynamicProperties;
use GenericDatabase\Shared\Run;
use GenericDatabase\Interfaces\IConnection;
use GenericDatabase\Interfaces\Connection\IOptions;
use GenericDatabase\Interfaces\Connection\IStatements;
use GenericDatabase\Interfaces\Connection\IReport;
use GenericDatabase\Generic\Statements\Metadata;

#[AllowDynamicProperties]
abstract class AbstractStatements implements IStatements
{
    protected static IConnection $instance;

    protected static IOptions $optionsHandler;

    protected static IReport $reportHandler;

    protected mixed $statement = null;

    protected Metadata $metadata;

    public function __construct(IConnection $instance, IOptions $optionsHandler, IReport $reportHandler)
    {
        self::$instance = $instance;
        self::$optionsHandler = $optionsHandler;
        self::$reportHandler = $reportHandler;
        $this->metadata = new Metadata();
    }

    public static function getInstance(): IConnection
    {
        return self::$instance;
    }

    public function set(string $name, mixed $value): void
    {
        Run::call([$this->getInstance(), 'set' . ucfirst($name)], $value);
    }

    public function get(string $name): mixed
    {
        return Run::call([$this->getInstance(), 'get' . ucfirst($name)]);
    }

    public function getOptionsHandler(): IOptions
    {
        return self::$optionsHandler;
    }

    public function getReportHandler(): IReport
    {
        return self::$reportHandler;
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
